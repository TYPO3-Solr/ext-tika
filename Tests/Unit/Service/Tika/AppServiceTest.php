<?php
namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Tika;

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

use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Tests\Unit\ExecRecorder;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Test case for class AppService
 *
 */
class AppServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Backup of current singleton instances
	 */
	protected $singletonInstances;

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
	protected $storageUid = 9000;

	protected function setUp() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		ExecRecorder::reset();

		// Disable xml2array cache used by ResourceFactory
		GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->setCacheConfigurations(array(
			'cache_hash' => array(
				'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend',
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend'
			)
		));

		$this->setUpStorageMock();

		$mockedMetaDataRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
		$mockedMetaDataRepository->expects($this->any())->method('findByFile')->will($this->returnValue(array('file' => 1)));
		GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', $mockedMetaDataRepository);
	}

	protected function setUpStorageMock() {
		$this->testDocumentsPath = ExtensionManagementUtility::extPath('tika')
			. 'Tests/TestDocuments/';

		$documentsDriver = $this->createDriverFixture(array(
			'basePath' => $this->testDocumentsPath,
			'caseSensitive' => TRUE
		));

		$documentsStorageRecord = array(
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

		$this->storageMock = $this->getMock('TYPO3\CMS\Core\Resource\ResourceStorage', NULL, array($documentsDriver, $documentsStorageRecord));
		$this->storageMock->expects($this->any())->method('getUid')->will($this->returnValue($this->storageUid));
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

	protected function tearDown() {
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * Creates Tika App configuration
	 *
	 * @return array
	 */
	protected function getTikaAppConfiguration() {
		$tikaVersion = getenv('TIKA_VERSION') ? getenv('TIKA_VERSION') : '1.10';
		$tikaPath    = getenv('TIKA_PATH') ? getenv('TIKA_PATH') : '/opt/tika';

		return array(
			'tikaPath' => "$tikaPath/tika-app-$tikaVersion.jar",
		);
	}

	/**
	 * @test
	 */
	public function getTikaVersionUsesVParameter() {
		$service = new AppService($this->getTikaAppConfiguration());
		$service->getTikaVersion();

		$this->assertContains('-V', ExecRecorder::$execCommand);
	}

	/**
	 * @test
	 */
	public function extractTextUsesTParameter() {
		$file = new File(
			array(
				'identifier' => 'testWORD.doc',
				'name' => 'testWORD.doc'
			),
			$this->storageMock
		);

		$service = new AppService($this->getTikaAppConfiguration());
		$service->extractText($file);

		$this->assertContains('-t', ExecRecorder::$execCommand);
	}
}
