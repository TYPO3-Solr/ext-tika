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
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;


/**
 * Class ServerServiceTest
 *
 */
class SolrCellServiceTest extends AbstractServiceTest
{

    /**
     * @test
     */
    public function extractsMetaDataFromMp3File()
    {
        $service = new SolrCellService($this->getSolrCellConfiguration());
        $file = new File(['identifier' => 'testMP3.mp3', 'name' => 'testMP3.mp3'], $this->documentsStorageMock);
        $this->assertTrue(in_array($file->getMimeType(), $service->getSupportedMimeTypes()));
        $metaData = $service->extractMetaData($file);
        $this->assertEquals('audio/mpeg', $metaData['Content-Type']);
        $this->assertEquals('Test Title', $metaData['title']);
    }

    /**
     * Creates Tika Server connection configuration pointing to
     * http://localhost:9998
     *
     * @return array
     */
    protected function getSolrCellConfiguration()
    {
        return [
            'solrScheme' => 'http',
            'solrHost' => 'localhost',
            'solrPath' => '/solr/core_en',
            'solrPort' => '8999'
        ];
    }

}
