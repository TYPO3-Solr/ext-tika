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
use TYPO3\CMS\Core\Resource\File;

/**
 * Class ServerServiceTest
 *
 */
class ServerServiceTest extends AbstractServiceTest
{
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
