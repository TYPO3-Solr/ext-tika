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

use ApacheSolrForTypo3\Tika\Process;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika\Fixtures\ServerServiceFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\NullLogger;
use Throwable;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ServerServiceTest
 */
class ServerServiceTest extends ServiceIntegrationTestCase
{
    /**
     * @throws MockObjectException
     */
    public function getServerServiceTestable(): ServerService
    {
        $this->getRegistryMockObject()
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('tx_tika', 'server.pid')
            ->willReturn('');

        /** @var Process|MockObject $processMock */
        $processMock = $this->createMock(Process::class);
        $processMock
            ->expects(self::atLeastOnce())
            ->method('findPid')
            ->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock);

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        return $service;
    }

    /**
     * @throws MockObjectException
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getRegistryMockObject(array $onlyMethods = ['get', 'set', 'remove']): Registry|MockObject
    {
        /** @var Registry|MockObject $registryMock */
        $registryMock = $this->createMock(Registry::class);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock);
        return $registryMock;
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function startServerStoresPidInRegistry(): void
    {
        $this->getRegistryMockObject()
            ->expects(self::atLeastOnce())
            ->method('set')
            ->with('tx_tika', 'server.pid', 1000)
            ->willReturnCallback(function ($namespace, $key, $value) {
                self::assertIsInt($value);
                self::assertEquals(1000, $value);
            });

        /** @var Process|MockObject $processMock */
        $processMock = $this->createMock(Process::class);
        $processMock
            ->expects(self::atLeastOnce())
            ->method('start');
        $processMock
            ->expects(self::any())
            ->method('getPid')
            ->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock);

        // execute
        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->startServer();
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function stopServerRemovesPidFromRegistry(): void
    {
        $registryMock = $this->getRegistryMockObject();
        $registryMock
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('tx_tika', 'server.pid')
            ->willReturn(1000);
        $registryMock
            ->expects(self::atLeastOnce())
            ->method('remove')
            ->with('tx_tika', 'server.pid');

        /** @var Process|MockObject $processMock */
        $processMock = $this->getMockBuilder(Process::class)
            ->setConstructorArgs([''])
            ->onlyMethods(['setPid', 'stop'])
            ->getMock();
        $processMock
            ->expects(self::atLeastOnce())
            ->method('setPid')
            ->with(1000);
        $processMock
            ->expects(self::atLeastOnce())
            ->method('stop');
        GeneralUtility::addInstance(Process::class, $processMock);

        // execute
        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->stopServer();
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function getServerPidGetsPidFromRegistry(): void
    {
        $this->getRegistryMockObject()
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('tx_tika', 'server.pid')
            ->willReturn(1000);

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());

        self::assertEquals(1000, $service->getServerPid());
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function getServerPidFallsBackToProcess(): void
    {
        $service = $this->getServerServiceTestable();
        $pid = $service->getServerPid();

        self::assertEquals(1000, $pid);
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function isServerRunningReturnsTrueForRunningServerFromRegistry(): void
    {
        $this->getRegistryMockObject()
            ->expects(self::atLeastOnce())
            ->method('get')
            ->with('tx_tika', 'server.pid')
            ->willReturn(1000);

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertTrue($service->isServerRunning());
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function isServerRunningReturnsTrueForRunningServerFromProcess(): void
    {
        $service = $this->getServerServiceTestable();
        self::assertTrue($service->isServerRunning());
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function isServerRunningReturnsFalseForStoppedServer(): void
    {
        $this->getRegistryMockObject()
            ->expects(self::any())
            ->method('get')
            ->with('tx_tika', 'server.pid')
            ->willReturn('');

        /** @var Process|MockObject $processMock */
        $processMock = $this->createMock(Process::class);
        $processMock
            ->expects(self::any())
            ->method('findPid')
            ->willReturn(null);
        GeneralUtility::addInstance(Process::class, $processMock);

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertFalse($service->isServerRunning());
    }

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
            ]
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
            $service->getRecordedEndpoint()
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
            $service->getRecordedEndpoint()
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
        self::assertEquals('Microsoft Office Word', $metaData['Application-Name']);
        self::assertEquals('Keith Bennett', $metaData['Author']);
        self::assertEquals('', $metaData['Company']);
        self::assertEquals('2010-11-12T16:22:00Z', $metaData['Creation-Date']);
        self::assertEquals('Nick Burch', $metaData['Last-Author']);
        self::assertEquals('2010-11-12T16:22:00Z', $metaData['Last-Save-Date']);
        self::assertEquals('2', $metaData['Page-Count']);
        self::assertEquals('2', $metaData['Revision-Number']);
        self::assertEquals('Normal.dotm', $metaData['Template']);
        self::assertEquals('Sample Word Document', $metaData['title']);
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
            ]
        );

        $metaData = $service->extractMetaData($fileMock);

        self::assertEquals('audio/mpeg', $metaData['Content-Type']);
        self::assertEquals('Test Title', $metaData['title']);
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
            ]
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
                $this->languagesStorageMock
            )
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
            ]
        );
    }
}
