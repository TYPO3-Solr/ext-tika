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

use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\Service\Tika\Fixtures\ServerServiceFixture;
use Prophecy\Argument;
use Prophecy\Prophet;
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
	 * @var Prophet
	 */
	protected $prophet;

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


	protected function setup() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		$this->prophet = new Prophet;

		$this->setUpDocumentsStorageMock();

		$mockedMetaDataRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
		$mockedMetaDataRepository->expects($this->any())->method('findByFile')->will($this->returnValue(array('file' => 1)));
		GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository', $mockedMetaDataRepository);
	}

	protected function setUpDocumentsStorageMock() {
		$this->testDocumentsPath = ExtensionManagementUtility::extPath('tika')
				. 'Tests/TestDocuments/';

		$documentsDriver = $this->createDriverFixture(array(
				'basePath'      => $this->testDocumentsPath,
				'caseSensitive' => TRUE
		));

		$documentsStorageRecord = array(
				'uid'           => $this->storageUid,
				'is_public'     => TRUE,
				'is_writable'   => FALSE,
				'is_browsable'  => TRUE,
				'is_online'     => TRUE,
				'configuration' => $this->convertConfigurationArrayToFlexformXml(array(
						'basePath'      => $this->testDocumentsPath,
						'pathType'      => 'absolute',
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
		$this->prophet->checkPredictions();
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
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
	public function startServerStoresPidInRegistry() {
		// prepare
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$processMock = $this->prophet->prophesize('ApacheSolrForTypo3\Tika\Process');
		$processMock->start()->shouldBeCalled();
		$processMock->getPid()->willReturn(1000);
		GeneralUtility::addInstance('ApacheSolrForTypo3\Tika\Process', $processMock->reveal());

		// execute
		$service = new ServerService($this->getTikaServerConfiguration());
		$service->startServer();

		// test
		$registryMock->set('tx_tika', 'server.pid', Argument::that(function ($arg) {
			return (is_int($arg) && $arg == 1000);
		}))->shouldHaveBeenCalled();
	}

	/**
	 * @test
	 */
	public function stopServerRemovesPidFromRegistry() {
		// prepare
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
		$registryMock->remove('tx_tika', 'server.pid')->shouldBeCalled();
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$processMock = $this->prophet->prophesize('ApacheSolrForTypo3\Tika\Process');
		$processMock->setPid(1000)->shouldBeCalled();
		$processMock->stop()->shouldBeCalled();
		GeneralUtility::addInstance('ApacheSolrForTypo3\Tika\Process', $processMock->reveal());

		// execute
		$service = new ServerService($this->getTikaServerConfiguration());
		$service->stopServer();
	}

	/**
	 * @test
	 */
	public function getServerPidGetsPidFromRegistry() {
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$service = new ServerService($this->getTikaServerConfiguration());
		$pid = $service->getServerPid();

		$this->assertEquals(1000, $pid);
	}

	/**
	 * @test
	 */
	public function getServerPidFallsBackToProcess() {
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn('');
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$processMock = $this->prophet->prophesize('ApacheSolrForTypo3\Tika\Process');
		$processMock->findPid()->willReturn(1000);
		GeneralUtility::addInstance('ApacheSolrForTypo3\Tika\Process', $processMock->reveal());

		$service = new ServerService($this->getTikaServerConfiguration());
		$pid = $service->getServerPid();

		$this->assertEquals(1000, $pid);
	}

	/**
	 * @test
	 */
	public function isServerRunningReturnsTrueForRunningServerFromRegistry() {
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$service = new ServerService($this->getTikaServerConfiguration());
		$this->assertTrue($service->isServerRunning());
	}

	/**
	 * @test
	 */
	public function isServerRunningReturnsTrueForRunningServerFromProcess() {
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn('');
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$processMock = $this->prophet->prophesize('ApacheSolrForTypo3\Tika\Process');
		$processMock->findPid()->willReturn(1000);
		GeneralUtility::addInstance('ApacheSolrForTypo3\Tika\Process', $processMock->reveal());

		$service = new ServerService($this->getTikaServerConfiguration());
		$this->assertTrue($service->isServerRunning());
	}

	/**
	 * @test
	 */
	public function isServerRunningReturnsFalseForStoppedServer() {
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn('');
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$processMock = $this->prophet->prophesize('ApacheSolrForTypo3\Tika\Process');
		$processMock->findPid()->willReturn('');
		GeneralUtility::addInstance('ApacheSolrForTypo3\Tika\Process', $processMock->reveal());

		$service = new ServerService($this->getTikaServerConfiguration());
		$this->assertFalse($service->isServerRunning());
	}

	/**
	 * @test
	 */
	public function getTikaUrlBuildsUrlFromConfiguration() {
		$service = new ServerService($this->getTikaServerConfiguration());
		$this->assertEquals('http://localhost:9998', $service->getTikaServerUrl());
	}

	/**
	 * @test
	 */
	public function extractTextQueriesTikaEndpoint() {
		$file = new File(
			array(
				'identifier' => 'testWORD.doc',
				'name'       => 'testWORD.doc'
			),
			$this->storageMock
		);

		$service = new ServerServiceFixture($this->getTikaServerConfiguration());
		$service->extractText($file);

		$this->assertEquals('/tika', $service->getRecordedEndpoint());
	}

	/**
	 * @test
	 */
	public function extractMetaDataQueriesMetaEndpoint() {
		$file = new File(
			array(
				'identifier' => 'testWORD.doc',
				'name'       => 'testWORD.doc'
			),
			$this->storageMock
		);

		$service = new ServerServiceFixture($this->getTikaServerConfiguration());
		$service->extractMetaData($file);

		$this->assertEquals('/meta', $service->getRecordedEndpoint());
	}

	/**
	 * @test
	 */
	public function detectLanguageFromFileQueriesLanguageStreamEndpoint() {
		$file = new File(
			array(
				'identifier' => 'testWORD.doc',
				'name'       => 'testWORD.doc'
			),
			$this->storageMock
		);

		$service = new ServerServiceFixture($this->getTikaServerConfiguration());
		$service->detectLanguageFromFile($file);

		$this->assertEquals('/language/stream', $service->getRecordedEndpoint());
	}
}
