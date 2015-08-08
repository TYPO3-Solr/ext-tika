<?php
namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service;

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

use ApacheSolrForTypo3\Tika\Service\Tika\TikaServiceFactory;


/**
 * Class AppServiceTest
 *
 */
class TikaServiceFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getTikaReturnsAppServiceForJarExtractor() {
		$extractor = TikaServiceFactory::getTika('jar');
		$this->assertInstanceOf('\ApacheSolrForTypo3\Tika\Service\Tika\AppService', $extractor);
	}

	/**
	 * @test
	 */
	public function getTikaReturnsAppServiceForTikaExtractor() {
		$extractor = TikaServiceFactory::getTika('tika');
		$this->assertInstanceOf('\ApacheSolrForTypo3\Tika\Service\Tika\AppService', $extractor);
	}

	/**
	 * @test
	 */
	public function getTikaReturnsServerServiceForServerExtractor() {
		$extractor = TikaServiceFactory::getTika('server');
		$this->assertInstanceOf('\ApacheSolrForTypo3\Tika\Service\Tika\ServerService', $extractor);
	}

	/**
	 * @test
	 */
	public function getTikaReturnsSolrCellServiceForSolrExtractor() {
		$extractor = TikaServiceFactory::getTika('solr');
		$this->assertInstanceOf('\ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService', $extractor);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getTikaThrowsExceptionForInvalidExtractor() {
		$extractor = TikaServiceFactory::getTika('foo');
	}

}