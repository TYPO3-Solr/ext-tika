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

use ApacheSolrForTypo3\Solr\SolrService;
use ApacheSolrForTypo3\Solr\System\Solr\Service\SolrWriteService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellQuery;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Prophecy\Argument;
use Prophecy\Prophet;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


/**
 * Class AppServiceTest
 *
 */
class SolrCellServiceTest extends ServiceUnitTestCase
{

    /**
     * @var Prophet
     */
    protected $prophet;


    protected function assertPreConditions()
    {
        if (!ExtensionManagementUtility::isLoaded('solr')) {
            $this->markTestSkipped('EXT:solr is required for this test, but is not loaded.');
        }
    }

    protected function tearDown()
    {
        $this->verifyMockObjects();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function newInstancesAreInitializedWithASolrConnection()
    {
        $service = new SolrCellService($this->getConfiguration());
        $this->assertAttributeInstanceOf(SolrConnection::class, 'solrConnection', $service);
    }

    /**
     * @test
     */
    public function extractByQueryTextReturnsTextElementFromResponse()
    {
        $expectedValue = 'extracted text element';

        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(SolrCellQuery::class))
            ->willReturn([
                $expectedValue,     // extracted text is index 0
                'meta data element' // meta data is index 1
            ]);

        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $actualValue = $service->extractText($file);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function extractByQueryTextUsesSolrCellQuery()
    {
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(SolrCellQuery::class))->shouldBeCalled();

        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File([
            'identifier' => 'testWORD.doc',
            'name' => 'testWORD.doc'
        ],
            $this->documentsStorageMock
        );

        $service->extractText($file);
    }

    /**
     * @test
     */
    public function extractTextCleansUpTempFile()
    {
        $serviceMock = $this->getMockBuilder(SolrCellService::class)
            ->setConstructorArgs([$this->getConfiguration()])
            ->setMethods(['cleanupTempFile'])
            ->getMock();
        $serviceMock->expects($this->once())->method('cleanupTempFile');

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $serviceMock->extractText($file);
    }

    #TODO test return value, conversion of response to array

    /**
     * @test
     */
    public function extractMetaDataUsesSolrCellQuery()
    {
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(SolrCellQuery::class))
            ->shouldBeCalled()
            ->willReturn([
                    'foo', // extracted text is index 0
                    ['bar'] // meta data is index 1
                ]
            );

        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service->extractMetaData($file);
    }

    /**
     * @test
     */
    public function extractMetaDataCleansUpTempFile()
    {
        $serviceMock = $this->getMockBuilder(SolrCellService::class)
            ->setConstructorArgs([$this->getConfiguration()])
            ->setMethods(['cleanupTempFile'])
            ->getMock();
        $serviceMock->expects($this->once())->method('cleanupTempFile');

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $serviceMock->extractMetaData($file);
    }

}
