<?php

declare(strict_types=1);

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

namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika;

use ApacheSolrForTypo3\Solr\System\Solr\Service\SolrWriteService;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Solarium\QueryType\Extract\Query;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class AppServiceTest
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 *
 * @todo: Move duplicated code in methods.
 */
class SolrCellServiceTest extends ServiceIntegrationTestCase
{
    protected function assertPreConditions(): void
    {
        if (!ExtensionManagementUtility::isLoaded('solr')) {
            self::markTestSkipped('EXT:solr is required for this test, but is not loaded.');
        }
    }

    /**
     * @test
     */
    public function newInstancesAreInitializedWithASolrConnection(): void
    {
        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertTrue($service->isAvailable());
    }

    /**
     * @test
     */
    public function extractByQueryTextReturnsTextElementFromResponse(): void
    {
        $expectedValue = 'extracted text element';

        /* @var SolrWriteService|ObjectProphecy $solrWriter */
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(Query::class))
            ->willReturn([
                $expectedValue,     // extracted text is index 0
                'meta data element', // meta data is index 1
            ]);

        /* @var SolrConnection|ObjectProphecy $connectionMock */
        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $actualValue = $service->extractText($file);
        self::assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function extractByQueryTextUsesSolariumExtractQuery(): void
    {
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(Query::class))->shouldBeCalled();

        /* @var SolrConnection|ObjectProphecy $connectionMock */
        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $service->extractText($file);
    }

    //TODO test return value, conversion of response to array

    /**
     * @test
     */
    public function extractMetaDataUsesSolariumExtractQuery(): void
    {
        $solrWriter = $this->prophesize(SolrWriteService::class);
        $solrWriter->extractByQuery(Argument::type(Query::class))
            ->shouldBeCalled()
            ->willReturn(
                [
                    'foo', // extracted text is index 0
                    ['bar'], // meta data is index 1
                ]
            );

        /* @var SolrConnection|ObjectProphecy $connectionMock */
        $connectionMock = $this->prophesize(SolrConnection::class);
        $connectionMock->getWriteService()->shouldBeCalled()->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->inject($service, 'solrConnection', $connectionMock->reveal());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $service->extractMetaData($file);
    }

    /**
     * @test
     */
    public function extractsMetaDataFromMp3File(): void
    {
        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $mockedFile = $this->getMockedFileInstance(
            [
                'identifier' => 'testMP3.mp3',
                'name' => 'testMP3.mp3',
            ]
        );
        self::assertTrue(in_array($mockedFile->getMimeType(), $service->getSupportedMimeTypes()));
        $metaData = $service->extractMetaData($mockedFile);
        self::assertEquals('audio/mpeg', $metaData['Content-Type']);
        self::assertEquals('Test Title', $metaData['title']);
    }
}
