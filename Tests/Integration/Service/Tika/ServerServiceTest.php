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

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function startServerStoresPidInRegistry()
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
        $registryMock->set('tx_tika', 'server.pid',
            Argument::that(function ($arg) {
                return (is_int($arg) && $arg == 1000);
            }))->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function stopServerRemovesPidFromRegistry()
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
    public function getServerPidGetsPidFromRegistry()
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $pid = $service->getServerPid();

        $this->assertEquals(1000, $pid);
    }

    /**
     * @test
     */
    public function getServerPidFallsBackToProcess()
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

        $this->assertEquals(1000, $pid);
    }

    /**
     * @test
     */
    public function isServerRunningReturnsTrueForRunningServerFromRegistry()
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn(1000);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->assertTrue($service->isServerRunning());
    }

    /**
     * @test
     */
    public function isServerRunningReturnsTrueForRunningServerFromProcess()
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn('');
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->findPid()->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->assertTrue($service->isServerRunning());
    }

    /**
     * @test
     */
    public function isServerRunningReturnsFalseForStoppedServer()
    {
        $registryMock = $this->prophesize(Registry::class);
        $registryMock->get('tx_tika', 'server.pid')->willReturn('');
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->findPid()->willReturn('');
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        $service = new ServerService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $this->assertFalse($service->isServerRunning());
    }

    /**
     * @test
     */
    public function getTikaUrlBuildsUrlFromConfiguration()
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
        $this->assertEquals($expectedTikaAuthority, $service->getTikaServerUrl());
    }

    /**
     * @test
     */
    public function extractTextQueriesTikaEndpoint()
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractText($this->getMockedFileInstanceForTestWordDotDocFile());

        $this->assertEquals('/tika', $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function extractMetaDataQueriesMetaEndpoint()
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractMetaData($this->getMockedFileInstanceForTestWordDotDocFile());

        $this->assertEquals('/meta', $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function detectLanguageFromFileQueriesLanguageStreamEndpoint()
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromFile($this->getMockedFileInstanceForTestWordDotDocFile());

        $this->assertEquals('/language/stream',
            $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function detectLanguageFromStringQueriesLanguageStringEndpoint()
    {
        $service = new ServerServiceFixture($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromString('foo');

        $this->assertEquals(
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
            'tikaServerPort' => getenv($envVarNamePrefix . 'SERVER_PORT') ?: '9998'
        ];
    }

    /**
     * @test
     */
    public function extractsMetaDataFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $metaData = $service->extractMetaData($this->getMockedFileInstanceForTestWordDotDocFile());

        $this->assertEquals('application/msword', $metaData['Content-Type']);
        $this->assertEquals('Microsoft Office Word', $metaData['Application-Name']);
        $this->assertEquals('Keith Bennett', $metaData['Author']);
        $this->assertEquals('', $metaData['Company']);
        $this->assertEquals('2010-11-12T16:22:00Z', $metaData['Creation-Date']);
        $this->assertEquals('Nick Burch', $metaData['Last-Author']);
        $this->assertEquals('2010-11-12T16:22:00Z', $metaData['Last-Save-Date']);
        $this->assertEquals('2', $metaData['Page-Count']);
        $this->assertEquals('2', $metaData['Revision-Number']);
        $this->assertEquals('Normal.dotm', $metaData['Template']);
        $this->assertEquals('Sample Word Document', $metaData['title']);
    }

    /**
     * @test
     */
    public function extractsMetaDataFromMp3File()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $fileMock = $this->getMockedFileInstance(
            [
                'identifier' => 'testMP3.mp3',
                'name' => 'testMP3.mp3'
            ]
        );

        $metaData = $service->extractMetaData($fileMock);

        $this->assertEquals('audio/mpeg', $metaData['Content-Type']);
        $this->assertEquals('Test Title', $metaData['title']);
    }

    /**
     * @test
     */
    public function extractsTextFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $expectedText = 'Sample Word Document';
        $extractedText = $service->extractText($this->getMockedFileInstanceForTestWordDotDocFile());

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromZipFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $expectedTextFromWord = 'Sample Word Document';
        $extractedText = $service->extractText($this->getMockedFileInstance(
            [
                'identifier' => 'test-documents.zip',
                'name' => 'test-documents.zip'
            ]
        ));
        $expectedTextFromPDF= 'Tika - Content Analysis Toolkit';

        $this->assertContains($expectedTextFromWord, $extractedText);
        $this->assertContains($expectedTextFromPDF, $extractedText);
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
            'swedish' => ['sv']
        ];
    }

    /**
     * @test
     * @dataProvider languageFileDataProvider
     */
    public function detectsLanguageFromFile($language)
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $detectedLanguage = $service->detectLanguageFromFile(
            $this->getMockedFileInstance(
                [
                    'identifier' => $language . '.test',
                    'name' => $language . '.test'
                ],
                $this->languagesStorageMock
            )
        );

        $this->assertSame($language, $detectedLanguage);
    }

    /**
     * @test
     * @dataProvider languageFileDataProvider
     */
    public function detectsLanguageFromString($language)
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());

        $file = $this->testLanguagesPath . $language . '.test';
        $languageString = file_get_contents($file);

        $detectedLanguage = $service->detectLanguageFromString($languageString);

        $this->assertSame($language, $detectedLanguage);
    }

    /**
     * @test
     */
    public function canGetMimeTypesFromServerAndParseThem()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $mimeTypes = $service->getSupportedMimeTypes();
        $this->assertContains('application/pdf', $mimeTypes, 'Server did not indicate to support pdf documents');
        $this->assertContains('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $mimeTypes, 'Server did not indicate to support docx documents');
    }

    /**
     * @test
     */
    public function canPing()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $service->setLogger(new NullLogger());
        $pingResult = $service->ping();

        $this->assertTrue($pingResult, 'Could not ping tika server');
    }

    /**
     * @return MockObject|File
     */
    protected function getMockedFileInstanceForTestWordDotDocFile()
    {
        return $this->getMockedFileInstance(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ]
        );
    }
}
