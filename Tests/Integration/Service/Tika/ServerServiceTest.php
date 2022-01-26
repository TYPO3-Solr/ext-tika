<?php

declare(strict_types=1);
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

use ApacheSolrForTypo3\Tika\Process;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika\Fixtures\ServerServiceFixture;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ServerServiceTest
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
class ServerServiceTest extends ServiceIntegrationTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function startServerStoresPidInRegistry(): void
    {
        // prepare
        /* @var Registry|ObjectProphecy $registryMock */
        $registryMock = $this->prophesize(Registry::class);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        /* @var Process|ObjectProphecy $processMock */
        $processMock = $this->prophesize(Process::class);
        $processMock->start()->shouldBeCalled();
        $processMock->getPid()->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        // execute
        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->startServer();

        // test
        $registryMock->set(
            'tx_tika',
            'server.pid',
            Argument::that(function ($arg) {
                return is_int($arg) && $arg == 1000;
            })
        )->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function stopServerRemovesPidFromRegistry(): void
    {
        // prepare
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
        $registryMock->remove('tx_tika', 'server.pid')->shouldBeCalled();
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->setPid(1000)->shouldBeCalled();
        $processMock->stop()->shouldBeCalled();
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        // execute
        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->stopServer();
    }

    /**
     * @test
     */
    public function getServerPidGetsPidFromRegistry(): void
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $pid = $service->getServerPid();

        self::assertEquals(1000, $pid);
    }

    /**
     * @test
     */
    public function getServerPidFallsBackToProcess(): void
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn('');
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->findPid()->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $pid = $service->getServerPid();

        self::assertEquals(1000, $pid);
    }

    /**
     * @test
     */
    public function isServerRunningReturnsTrueForRunningServerFromRegistry(): void
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertTrue($service->isServerRunning());
    }

    /**
     * @test
     */
    public function isServerRunningReturnsTrueForRunningServerFromProcess(): void
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn('');
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->findPid()->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertTrue($service->isServerRunning());
    }

    /**
     * @test
     */
    public function isServerRunningReturnsFalseForStoppedServer(): void
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn('');
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->findPid()->willReturn('');
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        self::assertFalse($service->isServerRunning());
    }

    /**
     * @test
     */
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
     * @test
     */
    public function extractTextQueriesTikaEndpoint(): void
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractText($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertEquals('/tika', $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function extractMetaDataQueriesMetaEndpoint(): void
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractMetaData($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertEquals('/meta', $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
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
     * @test
     */
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
     *
     * @return array
     */
    protected function getTikaServerConfiguration()
    {
        $envVarNamePrefix = 'TESTING_TIKA_';

        return [
            'tikaServerScheme' => getenv($envVarNamePrefix . 'SERVER_SCHEME') ?: 'http',
            'tikaServerHost' => getenv($envVarNamePrefix . 'SERVER_HOST') ?: 'localhost',
            'tikaServerPort' => getenv($envVarNamePrefix . 'SERVER_PORT') ?: '9998',
        ];
    }

    /**
     * @test
     */
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
     * @test
     */
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
     * @test
     */
    public function extractsTextFromDocFile(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $expectedText = 'Sample Word Document';
        $extractedText = $service->extractText($this->getMockedFileInstanceForTestWordDotDocFile());

        self::assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
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
        $expectedTextFromPDF= 'Tika - Content Analysis Toolkit';

        self::assertContains($expectedTextFromWord, $extractedText);
        self::assertContains($expectedTextFromPDF, $extractedText);
    }

    /**
     * Data provider fro detectsLanguageFromFile
     *
     * @return array
     */
    public function languageFileDataProvider()
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
     * @test
     * @dataProvider languageFileDataProvider
     */
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
     * @test
     * @dataProvider languageFileDataProvider
     */
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
     * @test
     */
    public function canGetMimeTypesFromServerAndParseThem(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $mimeTypes = $service->getSupportedMimeTypes();
        self::assertContains('application/pdf', $mimeTypes, 'Server did not indicate to support pdf documents');
        self::assertContains('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $mimeTypes, 'Server did not indicate to support docx documents');
    }

    /**
     * @test
     */
    public function canPing(): void
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $pingResult = $service->ping();

        self::assertTrue($pingResult, 'Could not ping tika server');
    }

    /**
     * @return MockObject|File
     */
    protected function getMockedFileInstanceForTestWordDotDocFile()
    {
        return $this->getMockedFileInstance(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ]
        );
    }
}
