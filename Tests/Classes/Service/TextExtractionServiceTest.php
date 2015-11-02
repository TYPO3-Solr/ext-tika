<?php
namespace ApacheSolrForTypo3\Tika\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Ingo Renner <ingo@typo3.org>
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Unit tests for the text extraction service
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class TextExtractionServiceTest
{

    private $testDocumentsPath;
    private $originalServices;

    public function setUp()
    {
        // deactivate all other services, so that we can be sure to get a
        // tika service when using makeInstanceService()

        // backup
        $this->originalServices = $GLOBALS['T3_SERVICES'];

        // deactivate all services except tika
        foreach ($GLOBALS['T3_SERVICES']['textExtract'] as $serviceKey => $serviceInfo) {
            if ($serviceKey == 'Tx_Tika_TextExtract') {
                continue;
            }

            $GLOBALS['T3_SERVICES']['textExtract'][$serviceKey]['available'] = false;
        }

        $this->testDocumentsPath = ExtensionManagementUtility::extPath('tika') . 'Tests/TestDocuments/';
    }

    public function tearDown()
    {
        // restore services
        $GLOBALS['T3_SERVICES'] = $this->originalServices;
    }


    // TODO use data provider instead


    // MS Office


    /**
     * @test
     */
    public function extractsTextFromDocFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'doc');
        $service->setInputFile($this->testDocumentsPath . 'testWORD.doc',
            'doc');
        $service->process();

        $expectedText = 'Sample Word Document';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromDocxFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'docx');
        $service->setInputFile($this->testDocumentsPath . 'testWORD.docx',
            'docx');
        $service->process();

        $expectedText = 'Sample Word Document';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromXlsFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'xls');
        $service->setInputFile($this->testDocumentsPath . 'testEXCEL.xls',
            'xls');
        $service->process();

        $expectedText = 'Sample Excel Worksheet';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromXlsxFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'xlsx');
        $service->setInputFile($this->testDocumentsPath . 'testEXCEL.xlsx',
            'xlsx');
        $service->process();

        $expectedText = 'Sample Excel Worksheet';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromPptFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'ppt');
        $service->setInputFile($this->testDocumentsPath . 'testPPT.ppt', 'ppt');
        $service->process();

        $expectedText = 'Sample Powerpoint Slide';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromPptxFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'pptx');
        $service->setInputFile($this->testDocumentsPath . 'testPPT.pptx',
            'pptx');
        $service->process();

        $expectedText = 'Sample Powerpoint Slide';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }


    // OpenOffice.org


    /**
     * @test
     */
    public function extractsTextFromOdfFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'odf');
        $service->setInputFile($this->testDocumentsPath . 'testODFwithOOo3.odt',
            'odf');
        $service->process();

        $expectedText = 'Tika is part of the Lucene project.';
        $extractedText = $service->getOutput();
        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromOdtFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'odt');

        // OOo 3
        $service->setInputFile($this->testDocumentsPath . 'testODFwithOOo3.odt',
            'odt');
        $service->process();

        $expectedText = 'Apache Tika Test Document';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);

        // OOo 2
        $service->setInputFile($this->testDocumentsPath . 'testOpenOffice2.odt',
            'odt');
        $service->process();

        $expectedText = 'This is a sample Open Office document';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromSxwFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'sxw');

        // OOo 1
        $service->setInputFile($this->testDocumentsPath . 'testSXW.sxw', 'sxw');
        $service->process();

        $expectedText = 'Apache Tika Test Document';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }


    // XML and markup


    /**
     * @test
     */
    public function extractsTextFromXmlFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'xml');
        $service->setInputFile($this->testDocumentsPath . 'testXML.xml', 'xml');
        $service->process();

        $expectedText = 'Tika test document';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromHtmlFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'html');

        // HTML
        $service->setInputFile($this->testDocumentsPath . 'testHTML.html',
            'html');
        $service->process();

        $expectedText = 'Test Indexation Html';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);

        // HTML, utf8
        $service->setInputFile($this->testDocumentsPath . 'testHTML_utf8.html',
            'html');
        $service->process();

        $expectedText = 'åäö';    // &aring;&auml;&ouml;
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromEpubFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'epub');
        $service->setInputFile($this->testDocumentsPath . 'testEPUB.epub',
            'epub');
        $service->process();

        $expectedText = 'This is the text for chapter One';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }


    // Packages


    /**
     * @test
     */
    public function extractsTextFromTgzFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'tgz');
        $service->setInputFile($this->testDocumentsPath . 'test-documents.tgz',
            'zip');
        $service->process();

        $extractedText = $service->getOutput();

        $this->assertContains('test-documents/testEXCEL.xls', $extractedText);
        $this->assertContains('Sample Excel Worksheet', $extractedText);

        $this->assertContains('test-documents/testHTML.html', $extractedText);
        $this->assertContains('Test Indexation Html', $extractedText);

        $this->assertContains('test-documents/testOpenOffice2.odt',
            $extractedText);
        $this->assertContains('This is a sample Open Office document',
            $extractedText);

        $this->assertContains('test-documents/testPDF.pdf', $extractedText);
        $this->assertContains('Apache Tika', $extractedText);

        $this->assertContains('test-documents/testPPT.ppt', $extractedText);
        $this->assertContains('Sample Powerpoint Slide', $extractedText);

        $this->assertContains('test-documents/testRTF.rtf', $extractedText);
        $this->assertContains('indexation Word', $extractedText);

        $this->assertContains('test-documents/testTXT.txt', $extractedText);
        $this->assertContains('Test d\'indexation de Txt', $extractedText);

        $this->assertContains('test-documents/testWORD.doc', $extractedText);
        $this->assertContains('This is a sample Microsoft Word Document',
            $extractedText);

        $this->assertContains('test-documents/testXML.xml', $extractedText);
        $this->assertContains('Rida Benjelloun', $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromZipFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'zip');
        $service->setInputFile($this->testDocumentsPath . 'test-documents.zip',
            'zip');
        $service->process();

        $extractedText = $service->getOutput();

        $this->assertContains('testEXCEL.xls', $extractedText);
        $this->assertContains('Sample Excel Worksheet', $extractedText);

        $this->assertContains('testHTML.html', $extractedText);
        $this->assertContains('Test Indexation Html', $extractedText);

        $this->assertContains('testOpenOffice2.odt', $extractedText);
        $this->assertContains('This is a sample Open Office document',
            $extractedText);

        $this->assertContains('testPDF.pdf', $extractedText);
        $this->assertContains('Apache Tika', $extractedText);

        $this->assertContains('testPPT.ppt', $extractedText);
        $this->assertContains('Sample Powerpoint Slide', $extractedText);

        $this->assertContains('testRTF.rtf', $extractedText);
        $this->assertContains('indexation Word', $extractedText);

        $this->assertContains('testTXT.txt', $extractedText);
        $this->assertContains('Test d\'indexation de Txt', $extractedText);

        $this->assertContains('testWORD.doc', $extractedText);
        $this->assertContains('This is a sample Microsoft Word Document',
            $extractedText);

        $this->assertContains('testXML.xml', $extractedText);
        $this->assertContains('Rida Benjelloun', $extractedText);
    }


    // Other


    /**
     * @test
     */
    public function extractsTextFromMsgFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'msg');
        $service->setInputFile($this->testDocumentsPath . 'testMSG.msg', 'msg');
        $service->process();

        $expectedText = 'work has progressed pretty well';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromPdfFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'pdf');
        $service->setInputFile($this->testDocumentsPath . 'testPDF.pdf', 'pdf');
        $service->process();

        $expectedText = 'Tika - Content Analysis Toolkit';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromRtfFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'rtf');
        $service->setInputFile($this->testDocumentsPath . 'testRTF.rtf', 'rtf');
        $service->process();

        $expectedText = 'Test';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromTxtFile()
    {
        $service = GeneralUtility::makeInstanceService('textExtract', 'txt');
        $service->setInputFile($this->testDocumentsPath . 'testTXT.txt', 'txt');
        $service->process();

        $expectedText = 'Test';
        $extractedText = $service->getOutput();

        $this->assertContains($expectedText, $extractedText);
    }
}
