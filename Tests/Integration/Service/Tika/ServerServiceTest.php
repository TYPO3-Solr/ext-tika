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

use ApacheSolrForTypo3\Tika\Process;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika\Fixtures\ServerServiceFixture;
use Prophecy\Argument;
use Prophecy\Prophet;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class ServerServiceTest
 *
 */
class ServerServiceTest extends ServiceIntegrationTestCase
{

    protected function tearDown()
    {
        $this->verifyMockObjects();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function startServerStoresPidInRegistry()
    {
        // prepare
        $registryMock = $this->prophesize(Registry::class);
        GeneralUtility::setSingletonInstance(Registry::class, $registryMock->reveal());

        $processMock = $this->prophesize(Process::class);
        $processMock->start()->shouldBeCalled();
        $processMock->getPid()->willReturn(1000);
        GeneralUtility::addInstance(Process::class, $processMock->reveal());

        // execute
        $service = new ServerService($this->getConfiguration());
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
        $this->assertFalse($service->isServerRunning());
    }

    /**
     * @test
     */
    public function getTikaUrlBuildsUrlFromConfiguration()
    {
        $service = new ServerService($this->getConfiguration());
        $this->assertEquals('http://localhost:9998',
            $service->getTikaServerUrl());
    }

    /**
     * @test
     */
    public function extractTextQueriesTikaEndpoint()
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service = new ServerServiceFixture($this->getConfiguration());
        $service->extractText($file);

        $this->assertEquals('/tika', $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function extractMetaDataQueriesMetaEndpoint()
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service = new ServerServiceFixture($this->getConfiguration());
        $service->extractMetaData($file);

        $this->assertEquals('/meta', $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function detectLanguageFromFileQueriesLanguageStreamEndpoint()
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service = new ServerServiceFixture($this->getConfiguration());
        $service->detectLanguageFromFile($file);

        $this->assertEquals('/language/stream',
            $service->getRecordedEndpoint());
    }

    /**
     * @test
     */
    public function detectLanguageFromStringQueriesLanguageStringEndpoint()
    {
        $service = new ServerServiceFixture($this->getConfiguration());
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
        return [
            'tikaServerScheme' => 'http',
            'tikaServerHost' => 'localhost',
            'tikaServerPort' => '9998'
        ];
    }

    /**
     * @test
     */
    public function extractsMetaDataFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $metaData = $service->extractMetaData($file);

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

        $file = new File(['identifier' => 'testMP3.mp3', 'name' => 'testMP3.mp3'], $this->documentsStorageMock);

        $metaData = $service->extractMetaData($file);

        $this->assertEquals('audio/mpeg', $metaData['Content-Type']);
        $this->assertEquals('Test Title', $metaData['title']);
    }

    /**
     * @test
     */
    public function extractsTextFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $expectedText = 'Sample Word Document';
        $extractedText = $service->extractText($file);

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromZipFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => 'test-documents.zip',
                'name' => 'test-documents.zip'
            ],
            $this->documentsStorageMock
        );

        $expectedTextFromWord = 'Sample Word Document';
        $extractedText = $service->extractText($file);
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

        $file = new File(
            [
                'identifier' => $language . '.test',
                'name' => $language . '.test'
            ],
            $this->languagesStorageMock
        );

        $detectedLanguage = $service->detectLanguageFromFile($file);

        $this->assertSame($language, $detectedLanguage);
    }

    /**
     * @test
     * @dataProvider languageFileDataProvider
     */
    public function detectsLanguageFromString($language)
    {
        $service = new ServerService($this->getTikaServerConfiguration());

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
        $pingResult = $service->ping();

        $this->assertTrue($pingResult, 'Could not ping tika server');
    }
}
