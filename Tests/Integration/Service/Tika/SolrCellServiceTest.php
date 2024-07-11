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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class AppServiceTest
 *
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

    #[Test]
    public function newInstancesAreInitializedWithASolrConnection(): void
    {
        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertTrue($service->isAvailable());
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function extractByQueryTextReturnsTextElementFromResponse(): void
    {
        $expectedValue = 'extracted text element';

        /** @var SolrWriteService|MockObject $solrWriter */
        $solrWriter = $this->createMock(SolrWriteService::class);
        $solrWriter
            ->expects(self::atLeastOnce())
            ->method('extractByQuery')
            ->willReturn([
                $expectedValue,     // extracted text is index 0
                'meta data element', // meta data is index 1
            ]);

        $service = $this->createSolrCellServiceTestable($solrWriter);
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
     * @throws MockObjectException
     */
    #[Test]
    public function extractByQueryTextUsesSolariumExtractQuery(): void
    {
        $solrWriter = $this->createMock(SolrWriteService::class);
        $solrWriter
            ->expects(self::atLeastOnce())
            ->method('extractByQuery');

        $service = $this->createSolrCellServiceTestable($solrWriter);
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
     * @throws MockObjectException
     */
    #[Test]
    public function extractMetaDataUsesSolariumExtractQuery(): void
    {
        $solrWriter = $this->createMock(SolrWriteService::class);
        $solrWriter
            ->expects(self::atLeastOnce())
            ->method('extractByQuery')
            ->willReturn(
                [
                    'foo', // extracted text is index 0
                    ['bar'], // meta data is index 1
                ]
            );
        $service = $this->createSolrCellServiceTestable($solrWriter);
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $service->extractMetaData($file);
    }

    #[Test]
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

    /**
     * @throws MockObjectException
     */
    protected function createSolrCellServiceTestable(SolrWriteService|MockObject $solrWriter): SolrCellService
    {
        /** @var SolrConnection|MockObject $connectionMock */
        $connectionMock = $this->createMock(SolrConnection::class);
        $connectionMock
            ->expects(self::atLeastOnce())
            ->method('getWriteService')
            ->willReturn($solrWriter);

        $service = new SolrCellService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->inject($service, 'solrConnection', $connectionMock);
        return $service;
    }
}
