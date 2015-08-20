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
use Prophecy\Argument;
use Prophecy\Prophet;
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

	protected function setup() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		$this->prophet = new Prophet;
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
		$this->markTestIncomplete();
		return;

		$service = new ServerService($this->getTikaServerConfiguration());

	}

}
