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

use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika\Fixtures\ServerServiceFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\NullLogger;
use Throwable;
use TYPO3\CMS\Core\Resource\File;

/**
 * Class ServerServiceTest
 */
class ServerServiceTest extends ServiceIntegrationTestCase
{
    #[Test]
    public function getTikaUrlBuildsUrlFromConfiguration(): void
    {
        $tikaExtensionConfiguration = $this->getConfiguration();
        $service = new ServerService($tikaExtensionConfiguration);
        $service->setLogger(new NullLogger());

        $expectedTikaAuthority = vsprintf(
            '%s://%s:%s',
            [
                $tikaExtensionConfiguration['tikaServerScheme'],
                $tikaExtensionConfiguration['tikaServerHost'],
                $tikaExtensionConfiguration['tikaServerPort'],
            ],
        );
        self::assertEquals($expectedTikaAuthority, $service->getTikaServerUrl());
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function extractTextQueriesTikaEndpoint(): void
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractText($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertEquals('/tika', $service->getRecordedEndpoint());
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function extractMetaDataQueriesMetaEndpoint(): void
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractMetaData($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertEquals('/meta', $service->getRecordedEndpoint());
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function detectLanguageFromFileQueriesLanguageStreamEndpoint(): void
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromFile($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertEquals(
            '/language/stream',
            $service->getRecordedEndpoint(),
        );
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function detectLanguageFromStringQueriesLanguageStringEndpoint(): void
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromString('foo');

        self::assertEquals(
            '/language/string',
            $service->getRecordedEndpoint(),
        );
    }
    /**
     * Creates Tika Server connection configuration pointing to
     * http://localhost:9998
     */
    protected function getTikaServerConfiguration(): array
    {
        $envVarNamePrefix = 'TESTING_TIKA_';

        return [
            'tikaServerScheme' => getenv($envVarNamePrefix . 'SERVER_SCHEME') ?: 'http',
            'tikaServerHost' => getenv($envVarNamePrefix . 'SERVER_HOST') ?: 'localhost',
            'tikaServerPort' => getenv($envVarNamePrefix . 'SERVER_PORT') ?: '9998',
            'tikaServerUsername' => '',
            'tikaServerPassword' => '',
            'tikaServerConnectTimeoutMs' => 2000,
            'tikaServerRequestTimeoutMs' => 10000,
        ];
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function extractsMetaDataFromDocFile(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $metaData = $service->extractMetaData($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertEquals('application/msword', $metaData['Content-Type']);
        self::assertEquals('Microsoft Office Word', $metaData['extended-properties:Application']);
        self::assertEquals('Keith Bennett', $metaData['dc:creator']);
        self::assertEmpty($metaData['extended-properties:Company'] ?? '');
        self::assertEquals('2010-11-12T16:22:00Z', $metaData['dcterms:created']);
        self::assertEquals('Nick Burch', $metaData['meta:last-author']);
        self::assertEquals('2010-11-12T16:22:00Z', $metaData['dcterms:modified']);
        self::assertEquals('2', $metaData['meta:page-count']);
        self::assertEquals('2', $metaData['cp:revision']);
        self::assertEquals('Normal.dotm', $metaData['extended-properties:Template']);
        self::assertEquals('Sample Word Document', $metaData['dc:title']);
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function extractsMetaDataFromMp3File(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $fileMock = $this->getMockedFileInstance(
            [
                'identifier' => 'testMP3.mp3',
                'name' => 'testMP3.mp3',
            ],
        );

        $metaData = $service->extractMetaData($fileMock);

        self::assertEquals('audio/mpeg', $metaData['Content-Type']);
        self::assertEquals('Test Title', $metaData['dc:title']);
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function extractsTextFromDocFile(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $expectedText = 'Sample Word Document';
        $extractedText = $service->extractText($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertStringContainsString($expectedText, $extractedText);
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function extractsTextFromZipFile(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $expectedTextFromWord = 'Sample Word Document';
        $extractedText = $service->extractText($this->getMockedFileInstance(
            [
                'identifier' => 'test-documents.zip',
                'name' => 'test-documents.zip',
            ],
        ));
        $expectedTextFromPDF = 'Tika - Content Analysis Toolkit';

        self::assertStringContainsString($expectedTextFromWord, $extractedText);
        self::assertStringContainsString($expectedTextFromPDF, $extractedText);
    }

    /**
     * Data provider fro detectsLanguageFromFile
     */
    public static function languageFileDataProvider(): array
    {
        return [
            'danish' => ['da'],
            'german' => ['de'],
            'greek' => ['el'],
            'english' => ['en'],
            'spanish' => ['es'],
            'estonian' => ['et'],
            'finish' => ['fi'],
            'french' => ['fr'],
            'italian' => ['it'],
            'lithuanian' => ['lt'],
            'dutch' => ['nl'],
            'portuguese' => ['pt'],
            'swedish' => ['sv'],
        ];
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    #[DataProvider('languageFileDataProvider')]
    public function detectsLanguageFromFile($language): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $detectedLanguage = $service->detectLanguageFromFile(
            $this->getMockedFileInstance(
                [
                    'identifier' => $language . '.test',
                    'name' => $language . '.test',
                ],
                $this->languagesStorageMock,
            ),
        );

        self::assertSame($language, $detectedLanguage);
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    #[DataProvider('languageFileDataProvider')]
    public function detectsLanguageFromString($language): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $file = $this->testLanguagesPath . $language . '.test';
        $languageString = file_get_contents($file);

        $detectedLanguage = $service->detectLanguageFromString($languageString);

        self::assertSame($language, $detectedLanguage);
    }

    /**
     * @throws Throwable
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function canGetMimeTypesFromServerAndParseThem(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $mimeTypes = $service->getSupportedMimeTypes();
        self::assertContains('application/pdf', $mimeTypes, 'Server did not indicate to support pdf documents');
        self::assertContains('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $mimeTypes, 'Server did not indicate to support docx documents');
    }

    #[Test]
    public function canPing(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $pingResult = $service->ping();

        self::assertTrue($pingResult, 'Could not ping tika server');
    }

    protected function getMockedFileInstanceForTestWordDotDocFile(): File|MockObject
    {
        return $this->getMockedFileInstance(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
        );
    }
}
