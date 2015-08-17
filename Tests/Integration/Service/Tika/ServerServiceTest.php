<?php
namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class ServerServiceTest
 *
 */
class ServerServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var string
	 */
	protected $testDocumentsPath;

	/**
	 * @var ResourceStorage
	 */
	protected $storageMock;

	/**
	 * @var int
	 */
	protected $storageUid = 9998;


	public function setUp() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();

		$this->testDocumentsPath = ExtensionManagementUtility::extPath('tika')
			. 'Tests/TestDocuments/';

		$driver = $this->createDriverFixture(array(
			'basePath' => $this->testDocumentsPath,
			'caseSensitive' => TRUE
		));
		$storageRecord = array(
			'uid' => $this->storageUid,
			'is_public' => TRUE,
			'is_writable' => FALSE,
			'is_browsable' => TRUE,
			'is_online' => TRUE,
			'configuration' => $this->convertConfigurationArrayToFlexformXml(array(
				'basePath' => $this->testDocumentsPath,
				'pathType' => 'absolute',
				'caseSensitive' => '1'
			))
		);

		$this->storageMock = $this->getMock('TYPO3\CMS\Core\Resource\ResourceStorage', NULL, array($driver, $storageRecord));
		$this->storageMock->expects($this->any())->method('getUid')->will($this->returnValue($this->storageUid));

		$mockedMetaDataRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
		$mockedMetaDataRepository->expects($this->any())->method('findByFile')->will($this->returnValue(array('file' => 1)));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', $mockedMetaDataRepository);
	}

	public function tearDown() {
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * Creates a driver fixture object.
	 *
	 * @param array $driverConfiguration
	 * @param array $mockedDriverMethods
	 * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver
	 */
	protected function createDriverFixture(array $driverConfiguration = array(), $mockedDriverMethods = array()) {
		/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $driver */
		$mockedDriverMethods[] = 'isPathValid';
		$driver = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver', $mockedDriverMethods, array($driverConfiguration));
		$driver->expects($this->any())
			->method('isPathValid')
			->will(
				$this->returnValue(TRUE)
			);

		$driver->setStorageUid($this->storageUid);
		$driver->processConfiguration();
		$driver->initialize();
		return $driver;
	}

	/**
	 * Converts a simple configuration array into a FlexForm data structure serialized as XML
	 *
	 * @param array $configuration
	 * @return string
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml()
	 */
	protected function convertConfigurationArrayToFlexformXml(array $configuration) {
		$flexformArray = array('data' => array('sDEF' => array('lDEF' => array())));
		foreach ($configuration as $key => $value) {
			$flexformArray['data']['sDEF']['lDEF'][$key] = array('vDEF' => $value);
		}
		$configuration = GeneralUtility::array2xml($flexformArray);
		return $configuration;
	}

	/**
	 * Creates Tika Server connection configuration pointing to
	 * http://localhost:9998
	 *
	 * @return array
	 */
	protected function getTikaServerConfiguration() {
		return array(
			'tikaServerHost' => 'localhost',
			'tikaServerPort' => '9998'
		);
	}


	/**
	 * @test
	 */
	public function extractsMetaDataFromDocFile() {
		$service = new ServerService($this->getTikaServerConfiguration());

		$file = new File(
			array(
				'identifier' => 'testWORD.doc',
				'name'       => 'testWORD.doc'
			),
			$this->storageMock
		);

		$metaData = $service->extractMetaData($file);

		$this->assertEquals('application/msword', $metaData['Content-Type']);
		$this->assertEquals('Microsoft Word 10.1', $metaData['Application-Name']);
		$this->assertEquals('Keith Bennett', $metaData['Author']);
		$this->assertEquals('-', $metaData['Company']);
		$this->assertEquals('2007-09-12T20:31:00Z', $metaData['Creation-Date']);
		$this->assertArrayHasKey('Keywords', $metaData); // no keywords filled out in test file
		$this->assertEquals('Keith Bennett', $metaData['Last-Author']);
		$this->assertEquals('2007-09-12T20:38:00Z', $metaData['Last-Save-Date']);
		$this->assertEquals('1', $metaData['Page-Count']);
		$this->assertEquals('1', $metaData['Revision-Number']);
		$this->assertEquals('Normal', $metaData['Template']);
		$this->assertEquals('Sample Word Document', $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsTextFromDocFile() {
		$this->markTestIncomplete();

		$service = new ServerService($this->getTikaServerConfiguration());

		$expectedText = 'Sample Word Document';
		$extractedText = $service->extractText();

		$this->assertContains($expectedText, $extractedText);
	}

}
