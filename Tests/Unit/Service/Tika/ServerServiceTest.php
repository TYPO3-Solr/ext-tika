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
class ServerServiceTest extends ServiceUnitTestCase {

	/**
	 * @var Prophet
	 */
	protected $prophet;


	public function setup() {
		parent::setUp();
		$this->prophet = new Prophet;
	}

	public function tearDown() {
		$this->prophet->checkPredictions();
		parent::tearDown();
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
		$service = new ServerService($this->getConfiguration());
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
		$service = new ServerService($this->getConfiguration());
		$service->stopServer();
	}

	/**
	 * @test
	 */
	public function getServerPidGetsPidFromRegistry() {
		$registryMock = $this->prophet->prophesize('TYPO3\CMS\Core\Registry');
		$registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
		GeneralUtility::setSingletonInstance('TYPO3\CMS\Core\Registry', $registryMock->reveal());

		$service = new ServerService($this->getConfiguration());
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

		$service = new ServerService($this->getConfiguration());
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

		$service = new ServerService($this->getConfiguration());
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

		$service = new ServerService($this->getConfiguration());
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

		$service = new ServerService($this->getConfiguration());
		$this->assertFalse($service->isServerRunning());
	}

	/**
	 * @test
	 */
	public function getTikaUrlBuildsUrlFromConfiguration() {
		$service = new ServerService($this->getConfiguration());
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
			$this->documentsStorageMock
		);

		$service = new ServerServiceFixture($this->getConfiguration());
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
			$this->documentsStorageMock
		);

		$service = new ServerServiceFixture($this->getConfiguration());
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
			$this->documentsStorageMock
		);

		$service = new ServerServiceFixture($this->getConfiguration());
		$service->detectLanguageFromFile($file);

		$this->assertEquals('/language/stream', $service->getRecordedEndpoint());
	}

	/**
	 * @test
	 */
	public function detectLanguageFromStringQueriesLanguageStringEndpoint() {
		$service = new ServerServiceFixture($this->getConfiguration());
		$service->detectLanguageFromString('foo');

		$this->assertEquals('/language/string', $service->getRecordedEndpoint());
	}

}
