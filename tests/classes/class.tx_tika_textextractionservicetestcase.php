<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Ingo Renner <ingo@typo3.org>
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


/**
 * Unit tests for the text extraction service
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class tx_tika_TextExtractionServiceTestCase extends tx_phpunit_testcase {

	private $testDocumentsPath;
	private $originalServices;

	public function setUp() {
			// deactivate all other services, so that we can be sure to get a
			// tika service when using makeInstanceService()

			// backup
		$this->originalServices = $GLOBALS['T3_SERVICES'];

			// deactivate all services except tika
		foreach ($GLOBALS['T3_SERVICES']['textExtract'] as $serviceKey => $serviceInfo) {
			if ($serviceKey == 'tx_tika_textExtract') {
				continue;
			}

			$GLOBALS['T3_SERVICES']['textExtract'][$serviceKey]['available'] = FALSE;
		}

		$this->testDocumentsPath = t3lib_extMgm::extPath('tika') . 'tests/test-documents/';
	}

	public function tearDown() {
			// restore services
		$GLOBALS['T3_SERVICES'] = $this->originalServices;
	}

	/**
	 * @test
	 */
	public function extractsTextFromDocFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'doc');
		$service->setInputFile($this->testDocumentsPath . 'testWORD.doc', 'doc');
		$service->process();

		$expectedText  = 'Sample Word Document';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromDocxFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'docx');
		$service->setInputFile($this->testDocumentsPath . 'testWORD.docx', 'docx');
		$service->process();

		$expectedText  = 'Sample Word Document';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromEpubFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'epub');
		$service->setInputFile($this->testDocumentsPath . 'testEPUB.epub', 'epub');
		$service->process();

		$expectedText  = 'This is the text for chapter One';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromHtmlFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'html');

			// HTML
		$service->setInputFile($this->testDocumentsPath . 'testHTML.html', 'html');
		$service->process();

		$expectedText  = 'Test Indexation Html';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);

			// HTML, utf8
		$service->setInputFile($this->testDocumentsPath . 'testHTML_utf8.html', 'html');
		$service->process();

		$expectedText  = 'åäö';	// &aring;&auml;&ouml;
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromMsgFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'msg');
		$service->setInputFile($this->testDocumentsPath . 'testMSG.msg', 'msg');
		$service->process();

		$expectedText  = 'work has progressed pretty well';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromOdfFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'odf');
		$service->setInputFile($this->testDocumentsPath . 'testODFwithOOo3.odt', 'odf');
		$service->process();

		$expectedText  = 'Tika is part of the Lucene project.';
		$extractedText = $service->getOutput();
		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromOdtFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'odt');

			// OOo 3
		$service->setInputFile($this->testDocumentsPath . 'testODFwithOOo3.odt', 'odt');
		$service->process();

		$expectedText  = 'Apache Tika Test Document';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);

			// OOo 2
		$service->setInputFile($this->testDocumentsPath . 'testOpenOffice2.odt', 'odt');
		$service->process();

		$expectedText  = 'This is a sample Open Office document';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromPdfFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'pdf');
		$service->setInputFile($this->testDocumentsPath . 'testPDF.pdf', 'pdf');
		$service->process();

		$expectedText  = 'Tika - Content Analysis Toolkit';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromPptFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'ppt');
		$service->setInputFile($this->testDocumentsPath . 'testPPT.ppt', 'ppt');
		$service->process();

		$expectedText  = 'Sample Powerpoint Slide';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromPptxFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'pptx');
		$service->setInputFile($this->testDocumentsPath . 'testPPT.pptx', 'pptx');
		$service->process();

		$expectedText  = 'Sample Powerpoint Slide';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromRtfFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'rtf');
		$service->setInputFile($this->testDocumentsPath . 'testRTF.rtf', 'rtf');
		$service->process();

		$expectedText  = 'Test';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromSxwFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'sxw');

			// OOo 1
		$service->setInputFile($this->testDocumentsPath . 'testSXW.sxw', 'sxw');
		$service->process();

		$expectedText  = 'Apache Tika Test Document';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromTxtFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'txt');
		$service->setInputFile($this->testDocumentsPath . 'testTXT.txt', 'txt');
		$service->process();

		$expectedText  = 'Test';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromXlsFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'xls');
		$service->setInputFile($this->testDocumentsPath . 'testEXCEL.xls', 'xls');
		$service->process();

		$expectedText  = 'Sample Excel Worksheet';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromXlsxFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'xlsx');
		$service->setInputFile($this->testDocumentsPath . 'testEXCEL.xlsx', 'xlsx');
		$service->process();

		$expectedText  = 'Sample Excel Worksheet';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}

	/**
	 * @test
	 */
	public function extractsTextFromXmlFile() {
		$service = t3lib_div::makeInstanceService('textExtract', 'xml');
		$service->setInputFile($this->testDocumentsPath . 'testXML.xml', 'xml');
		$service->process();

		$expectedText  = 'Tika test document';
		$extractedText = $service->getOutput();

		$this->assertContains($expectedText, $extractedText);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_textextractionservice_testcase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_textextractionservice_testcase.php']);
}

?>