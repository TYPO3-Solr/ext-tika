<?php
namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use ApacheSolrForTypo3\Solr\SolrService;
use ApacheSolrForTypo3\Solr\System\Solr\Service\SolrWriteService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\NullLogger;
use Solarium\QueryType\Extract\Query;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class AppServiceTest
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
class SolrCellServiceTest extends ServiceIntegrationTestCase
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

    /**
     * @test
     */
    public function newInstancesAreInitializedWithASolrConnection()
    {
        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->assertAttributeInstanceOf(SolrConnection::class, 'solrConnection', $service);
    }

    /**
     * @test
     */
    public function extractByQueryTextReturnsTextElementFromResponse()
    {
        $expectedValue = 'extracted text element';

        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(Query::class))
            ->willReturn([
                $expectedValue,     // extracted text is index 0
                'meta data element' // meta data is index 1
            ]);

        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
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
    public function extractByQueryTextUsesSolariumExtractQuery()
    {
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(Query::class))->shouldBeCalled();

        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File([
            'identifier' => 'testWORD.doc',
            'name' => 'testWORD.doc'
        ],
            $this->documentsStorageMock
        );

        $service->extractText($file);
    }

    #TODO test return value, conversion of response to array

    /**
     * @test
     */
    public function extractMetaDataUsesSolariumExtractQuery()
    {
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(Query::class))
            ->shouldBeCalled()
            ->willReturn([
                    'foo', // extracted text is index 0
                    ['bar'] // meta data is index 1
                ]
            );

        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
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
    public function extractsMetaDataFromMp3File()
    {
        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $mockedFile = $this->getMockedFileInstance(
            [
                'identifier' => 'testMP3.mp3',
                'name' => 'testMP3.mp3'
            ]
        );
        $this->assertTrue(in_array($mockedFile->getMimeType(), $service->getSupportedMimeTypes()));
        $metaData = $service->extractMetaData($mockedFile);
        $this->assertEquals('audio/mpeg', $metaData['Content-Type']);
        $this->assertEquals('Test Title', $metaData['title']);
    }

}
