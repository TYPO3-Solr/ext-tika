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
 * Unit tests for the meta data extraction service
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class tx_tika_MetaDataExtractionServiceTestCase extends tx_phpunit_testcase {

	private $testDocumentsPath;
	private $originalServices;

	public function setUp() {
			// deactivate all other services, so that we can be sure to get a
			// tika service when using makeInstanceService()

			// backup
		$this->originalServices = $GLOBALS['T3_SERVICES'];

			// deactivate all services except tika
		foreach ($GLOBALS['T3_SERVICES']['metaExtract'] as $serviceKey => $serviceInfo) {
			if ($serviceKey == 'tx_tika_metaExtract') {
				continue;
			}

			$GLOBALS['T3_SERVICES']['metaExtract'][$serviceKey]['available'] = FALSE;
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
	public function extractsMetaDataFromAiffFile() {

#		$this->markTestIncomplete('aiff currently not working correctly.');

		$service = t3lib_div::makeInstanceService('metaExtract', 'aiff');
		$service->setInputFile($this->testDocumentsPath . 'testAIFF.aif', 'aiff');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('audio/x-aiff', $metaData['Content-Type']);
		$this->assertEquals('testAIFF.aif', $metaData['resourceName']);

		$this->assertEquals('44100',      $metaData['samplerate']);
		$this->assertEquals('2',          $metaData['channels']);
		$this->assertEquals('16',         $metaData['bits']);
		$this->assertEquals('PCM_SIGNED', $metaData['encoding']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromAuFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'au');
		$service->setInputFile($this->testDocumentsPath . 'testAU.au', 'au');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('audio/basic', $metaData['Content-Type']);
		$this->assertEquals('testAU.au',   $metaData['resourceName']);

		$this->assertEquals('16',         $metaData['bits']);
		$this->assertEquals('2',          $metaData['channels']);
		$this->assertEquals('PCM_SIGNED', $metaData['encoding']);
		$this->assertEquals('44100',      $metaData['samplerate']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromBmpFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'bmp');
		$service->setInputFile($this->testDocumentsPath . 'testBMP.bmp', 'bmp');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/bmp',   $metaData['Content-Type']);
		$this->assertEquals(75,            $metaData['Height']);
		$this->assertEquals(100,           $metaData['Width']);
		$this->assertEquals('testBMP.bmp', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromDocFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'doc');
		$service->setInputFile($this->testDocumentsPath . 'testWORD.doc', 'doc');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/msword', $metaData['Content-Type']);
		$this->assertEquals('testWORD.doc',       $metaData['resourceName']);

		$this->assertEquals('Microsoft Word 10.1',           $metaData['Application-Name']);
		$this->assertEquals('Keith Bennett',                 $metaData['Author']);
		$this->assertEquals('-',                             $metaData['Company']);
		$this->assertEquals('2007-09-12T20:31:00Z', $metaData['Creation-Date']);
		$this->assertArrayHasKey('Keywords', $metaData); // no keywords filled out in test file
		$this->assertEquals('Keith Bennett',                 $metaData['Last-Author']);
		$this->assertEquals('2007-09-12T20:38:00Z', $metaData['Last-Save-Date']);
		$this->assertEquals('1',                             $metaData['Page-Count']);
		$this->assertEquals('1',                             $metaData['Revision-Number']);
		$this->assertEquals('Normal',                        $metaData['Template']);
		$this->assertArrayHasKey('subject', $metaData); // no subject filled out in test file
		$this->assertEquals('Sample Word Document',          $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromDocxFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'docx');
		$service->setInputFile($this->testDocumentsPath . 'testWORD.docx', 'docx');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $metaData['Content-Type']); // sick
		$this->assertEquals('testWORD.docx', $metaData['resourceName']);

				// seems something's wrong with the date parser
		$this->assertEquals('Microsoft Office Word', $metaData['Application-Name']);
		$this->assertEquals('12.0000',               $metaData['Application-Version']);
		$this->assertEquals('Keith Bennett',         $metaData['Author']);
		$this->assertEquals('57',                    $metaData['Character Count']);
		$this->assertEquals('66',                    $metaData['Character-Count-With-Spaces']);
#		$this->assertEquals('2010-02-24T19:34:34Z',  $metaData['Last-Modified'], 'Last Modified');
#		$this->assertEquals('2010-02-24T19:34:32Z',  $metaData['Last-Printed'], 'Last Printed');
		$this->assertEquals('1',                     $metaData['Line-Count']);
		$this->assertEquals('1',                     $metaData['Page-Count']);
		$this->assertEquals('1',                     $metaData['Paragraph-Count']);
		$this->assertEquals('2',                     $metaData['Revision-Number']);
		$this->assertEquals('Normal.dotm',           $metaData['Template']);
		$this->assertEquals('10',                    $metaData['Word-Count']);
		$this->assertEquals('Keith Bennett',         $metaData['creator']);
#		$this->assertEquals('2010-02-24T19:34:32Z',  $metaData['date'], 'date');
		$this->assertEquals('-',                     $metaData['publisher']);
		$this->assertEquals('Sample Word Document',  $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromEpubFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'epub');
		$service->setInputFile($this->testDocumentsPath . 'testEPUB.epub', 'epub');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/epub+zip', $metaData['Content-Type']);
		$this->assertEquals('testEPUB.epub', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromFlvFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'flv');
		$service->setInputFile($this->testDocumentsPath . 'testFLV.flv', 'flv');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('video/x-flv', $metaData['Content-Type']);
		$this->assertEquals('testFLV.flv', $metaData['resourceName']);

		$this->assertEquals('true',      $metaData['hasAudio']);
		$this->assertEquals('false',     $metaData['stereo']);
		$this->assertEquals('2.0',       $metaData['audiocodecid']);
		$this->assertEquals('51.421875', $metaData['audiodatarate']);
		$this->assertEquals('22050.0',   $metaData['audiosamplerate']);
		$this->assertEquals('16.0',      $metaData['audiosamplesize']);
		$this->assertEquals('true',      $metaData['hasVideo']);
		$this->assertEquals('2.0',       $metaData['videocodecid']);
		$this->assertEquals('781.25',    $metaData['videodatarate']);
		$this->assertEquals('24.0',      $metaData['framerate']);
		$this->assertEquals('120',       $metaData['Height']);
		$this->assertEquals('170',       $metaData['Width']);
		$this->assertEquals('1.167',     $metaData['duration']);
		$this->assertEquals('90580.0',   $metaData['filesize']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromGifFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'gif');
		$service->setInputFile($this->testDocumentsPath . 'testGIF.gif', 'gif');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/gif', $metaData['Content-Type']);
		$this->assertEquals(75, $metaData['Height']);
		$this->assertEquals(100, $metaData['Width']);
		$this->assertEquals('testGIF.gif', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromHtmlFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'html');

			// HTML
		$service->setInputFile($this->testDocumentsPath . 'testHTML.html', 'html');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('text/html',     $metaData['Content-Type']);
		$this->assertEquals('testHTML.html', $metaData['resourceName']);

		$this->assertEquals('Tika Developers',              $metaData['Author']);
		$this->assertEquals('ISO-8859-1',                   $metaData['Content-Encoding']);
		$this->assertEquals('5',                            $metaData['refresh']);
		$this->assertEquals('Title : Test Indexation Html', $metaData['title']);

			// HTML, utf8
		$service->setInputFile($this->testDocumentsPath . 'testHTML_utf8.html', 'html');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('text/html',          $metaData['Content-Type']);
		$this->assertEquals('testHTML_utf8.html', $metaData['resourceName']);

		$this->assertEquals('UTF-8',                                 $metaData['Content-Encoding']);
		$this->assertEquals('Title : Tilte with UTF-8 chars öäå', $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromJpgFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'jpg');
		$service->setInputFile($this->testDocumentsPath . 'testJPEG.jpg', 'jpg');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/jpeg', $metaData['Content-Type']);
		$this->assertEquals(75, $metaData['Height']);
		$this->assertEquals(100, $metaData['Width']);
		$this->assertEquals('testJPEG.jpg', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsExifMetaDataFromJpgFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'image:exif');
		$service->setInputFile($this->testDocumentsPath . 'testJPEG_EXIF.jpg', 'jpg');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/jpeg', $metaData['Content-Type']);
		$this->assertEquals(68, $metaData['Height']);
		$this->assertEquals(100, $metaData['Width']);
		$this->assertEquals('testJPEG_EXIF.jpg', $metaData['resourceName']);
		$this->assertEquals('Canon EOS 40D', $metaData['Model']);
		$this->assertEquals('2009:08:11 09:09:45', $metaData['Date/Time Original']);
	}

	/**
	 * @test
	 */
	public function extractsExifMetaDataFromJpgFileIntoDamFields() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'image:exif');
		$service->setInputFile($this->testDocumentsPath . 'testJPEG_EXIF.jpg', 'jpg');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('Canon EOS 40D', $metaData['fields']['file_creator']);
		$this->assertEquals(1249974585,      $metaData['fields']['date_cr']);
		$this->assertEquals(240,             $metaData['fields']['hres'], 'Failed to provide horizontal resolution');
		$this->assertEquals(240,             $metaData['fields']['vres'], 'Failed to provide vertical resolution');
//		$this->assertArrayHasKey('color_space', $metaData['fields']); // test file has "undefined" color space
		$this->assertContains('canon-55-250', $metaData['fields']['keywords']);
		$this->assertArrayHasKey('copyright', $metaData['fields']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromMidFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'mid');
		$service->setInputFile($this->testDocumentsPath . 'testMID.mid', 'mid');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('audio/midi',  $metaData['Content-Type']);
		$this->assertEquals('testMID.mid', $metaData['resourceName']);

		$this->assertEquals('PPQ', $metaData['divisionType']);
		$this->assertEquals('0',   $metaData['patches']);
		$this->assertEquals('2',   $metaData['tracks']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromMp3File() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'mp3');
		$service->setInputFile($this->testDocumentsPath . 'testMP3.mp3', 'mp3');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('audio/mpeg',  $metaData['Content-Type']);
		$this->assertEquals('testMP3.mp3', $metaData['resourceName']);

		$this->assertEquals('Test Artist', $metaData['Author']);
		$this->assertEquals('Test Title',  $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromMsgFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'msg');
		$service->setInputFile($this->testDocumentsPath . 'testMSG.msg', 'msg');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.ms-outlook', $metaData['Content-Type']);
		$this->assertEquals('testMSG.msg',                $metaData['resourceName']);

		$this->assertEquals('Jukka Zitting',                   $metaData['Author']);
#		$this->assertEquals('MIME registry use cases',         $metaData['subject']);
		$this->assertEquals('MIME registry use cases', $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromOdfFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'odf');
		$service->setInputFile($this->testDocumentsPath . 'testOpenOffice2.odf', 'odf');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/zip', $metaData['Content-Type']);
		$this->assertEquals('testOpenOffice2.odf', $metaData['resourceName']);

			// TODO add more tests
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromOdtFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'odt');

			// OOo 2
		$service->setInputFile($this->testDocumentsPath . 'testOpenOffice2.odt', 'odt');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.oasis.opendocument.text', $metaData['Content-Type']);
		$this->assertEquals('testOpenOffice2.odt', $metaData['resourceName']);

			// OOo 3
		$service->setInputFile($this->testDocumentsPath . 'testODFwithOOo3.odt', 'odt');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.oasis.opendocument.text', $metaData['Content-Type']);
		$this->assertEquals('testODFwithOOo3.odt', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromPdfFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'pdf');
		$service->setInputFile($this->testDocumentsPath . 'testPDF.pdf', 'pdf');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/pdf', $metaData['Content-Type']);
		$this->assertEquals('testPDF.pdf',     $metaData['resourceName']);

		$this->assertEquals('Bertrand Delacrétaz',               $metaData['Author']);
		$this->assertEquals('Sat Sep 15 11:02:31 CEST 2007',      $metaData['Last-Modified']);
		$this->assertEquals('Sat Sep 15 11:02:31 CEST 2007',      $metaData['created']);
		$this->assertEquals('Firefox',                            $metaData['creator']);
		$this->assertEquals('Mac OS X 10.4.10 Quartz PDFContext', $metaData['producer']);
		$this->assertEquals('Apache Tika - Apache Tika',          $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromPngFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'png');
		$service->setInputFile($this->testDocumentsPath . 'testPNG.png', 'png');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/png', $metaData['Content-Type']);
		$this->assertEquals(75, $metaData['Height']);
		$this->assertEquals(100, $metaData['Width']);
		$this->assertEquals('testPNG.png', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromPptFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'ppt');
		$service->setInputFile($this->testDocumentsPath . 'testPPT.ppt', 'ppt');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.ms-powerpoint', $metaData['Content-Type']);
		$this->assertEquals('testPPT.ppt', $metaData['resourceName']);

		$this->assertEquals('Microsoft PowerPoint',          $metaData['Application-Name']);
		$this->assertEquals('Keith Bennett',                 $metaData['Author']);
		$this->assertEquals('-',                             $metaData['Company']);
		$this->assertEquals('Fri Sep 14 19:33:12 CEST 2007', $metaData['Creation-Date']);
		$this->assertEquals('Keith Bennett',                 $metaData['Last-Author']);
		$this->assertEquals('Fri Sep 14 21:16:39 CEST 2007', $metaData['Last-Save-Date']);
		$this->assertEquals('1',                             $metaData['Revision-Number']);
		$this->assertEquals('13',                            $metaData['Word-Count']);
		$this->assertEquals('Sample Powerpoint Slide',       $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromPptxFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'pptx');
		$service->setInputFile($this->testDocumentsPath . 'testPPT.pptx', 'pptx');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.openxmlformats-officedocument.presentationml.presentation', $metaData['Content-Type']); // sick
		$this->assertEquals('testPPT.pptx', $metaData['resourceName']);

		$this->assertEquals('Microsoft PowerPoint',  $metaData['Application-Name']);
		$this->assertEquals('12.0000',               $metaData['Application-Version']);
		$this->assertEquals('Keith Bennett',         $metaData['Author']);
		$this->assertEquals('garribas',              $metaData['Last-Author']);
		$this->assertEquals('2008-12-11T16:00:38Z',  $metaData['Last-Modified']);
		$this->assertEquals('2007-09-14T17:33:12Z',  $metaData['Last-Printed']);
		$this->assertEquals('4',                     $metaData['Paragraph-Count']);
		$this->assertEquals('On-screen Show (4:3)',  $metaData['Presentation-Format']);
		$this->assertEquals('1',                     $metaData['Revision-Number']);
		$this->assertEquals('1',                     $metaData['Slide-Count']);
		$this->assertEquals('13',                    $metaData['Word-Count']);
		$this->assertEquals('Keith Bennett',         $metaData['creator']);
		$this->assertEquals('2007-09-14T17:33:12Z',  $metaData['date']);
		$this->assertEquals('-',                     $metaData['publisher']);
		$this->assertEquals('Sample Powerpoint Slide',  $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromRtfFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'rtf');
		$service->setInputFile($this->testDocumentsPath . 'testRTF.rtf', 'rtf');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/rtf', $metaData['Content-Type']);
		$this->assertEquals('testRTF.rtf',     $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromSxwFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'sxw');
		$service->setInputFile($this->testDocumentsPath . 'testSXW.sxw', 'sxw');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.sun.xml.writer', $metaData['Content-Type']);
		$this->assertEquals('testSXW.sxw',                    $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromSvgFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'svg');
		$service->setInputFile($this->testDocumentsPath . 'testSVG.svg', 'svg');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/svg+xml', $metaData['Content-Type']);
		$this->assertEquals('testSVG.svg', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromTiffFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'tiff');
		$service->setInputFile($this->testDocumentsPath . 'testTIFF.tif', 'tif');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/tiff', $metaData['Content-Type']);
		$this->assertEquals('testTIFF.tif', $metaData['resourceName']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromTxtFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'txt');

			// en
		$service->setInputFile($this->testDocumentsPath . 'testTXT_en.txt', 'txt');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('text/plain',     $metaData['Content-Type']);
		$this->assertEquals('testTXT_en.txt', $metaData['resourceName']);

		$this->assertEquals('ISO-8859-1', $metaData['Content-Encoding']);
#		$this->assertEquals('en',         $metaData['Content-Language']);
#		$this->assertEquals('en',         $metaData['language']);

			// de
		$service->setInputFile($this->testDocumentsPath . 'testTXT_de.txt', 'txt');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('text/plain',     $metaData['Content-Type']);
		$this->assertEquals('testTXT_de.txt', $metaData['resourceName']);

		$this->assertEquals('ISO-8859-1', $metaData['Content-Encoding']);
#		$this->assertEquals('de',         $metaData['Content-Language']);
#		$this->assertEquals('de',         $metaData['language']);

			// fr
		$service->setInputFile($this->testDocumentsPath . 'testTXT.txt', 'txt');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('text/plain',  $metaData['Content-Type']);
		$this->assertEquals('testTXT.txt', $metaData['resourceName']);

		$this->assertEquals('ISO-8859-1', $metaData['Content-Encoding']);
#		$this->assertEquals('fr',         $metaData['Content-Language']);
#		$this->assertEquals('fr',         $metaData['language']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromWavFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'wav');
		$service->setInputFile($this->testDocumentsPath . 'testWAV.wav', 'wav');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('audio/x-wav',  $metaData['Content-Type']);
		$this->assertEquals('testWAV.wav', $metaData['resourceName']);

		$this->assertEquals('16',         $metaData['bits']);
		$this->assertEquals('2',          $metaData['channels']);
		$this->assertEquals('PCM_SIGNED', $metaData['encoding']);
		$this->assertEquals('44100',      $metaData['samplerate']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromXlsFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'xls');
		$service->setInputFile($this->testDocumentsPath . 'testEXCEL.xls', 'xls');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.ms-excel', $metaData['Content-Type']);
		$this->assertEquals('testEXCEL.xls',            $metaData['resourceName']);

		$this->assertEquals('Microsoft Excel',               $metaData['Application-Name']);
		$this->assertEquals('Keith Bennett',                 $metaData['Author']);
		$this->assertEquals('',                              $metaData['Company']);
		$this->assertEquals('Mon Oct 01 18:13:56 CEST 2007', $metaData['Creation-Date']);
		$this->assertEquals('RIBEN9',                        $metaData['Last-Author']);
		$this->assertEquals('Mon Oct 01 18:31:43 CEST 2007', $metaData['Last-Save-Date']);
		$this->assertEquals('Simple Excel document',         $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromXlsxFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'xlsx');
		$service->setInputFile($this->testDocumentsPath . 'testEXCEL.xlsx', 'xlsx');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $metaData['Content-Type']); // sick
		$this->assertEquals('testEXCEL.xlsx',            $metaData['resourceName']);

				// seems something's wrong with the date parser
		$this->assertEquals('Microsoft Excel',       $metaData['Application-Name']);
		$this->assertEquals('12.0000',               $metaData['Application-Version']);
		$this->assertEquals('Keith Bennett',         $metaData['Author']);
#		$this->assertEquals('2010-02-24T19:34:34Z',  $metaData['Last-Modified'], 'Last Modified');
#		$this->assertEquals('2010-02-24T19:34:32Z',  $metaData['Last-Printed'], 'Last Printed');
		$this->assertEquals('Keith Bennett',         $metaData['creator']);
#		$this->assertEquals('2010-02-24T19:34:32Z',  $metaData['date'], 'date');
		$this->assertEquals('',                      $metaData['publisher']);
		$this->assertEquals('Simple Excel document', $metaData['title']);
	}

	/**
	 * @test
	 */
	public function extractsMetaDataFromXmlFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'xml');
		$service->setInputFile($this->testDocumentsPath . 'testXML.xml', 'xml');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('application/xml', $metaData['Content-Type']);
		$this->assertEquals('testXML.xml',     $metaData['resourceName']);

		$this->assertEquals('Rida Benjelloun',                   $metaData['creator']);
		$this->assertEquals('2000-12',                           $metaData['date']);
		$this->assertContains('Framework d\'indexation des documents XML, HTML, PDF etc..',  $metaData['description']);
		$this->assertEquals('application/msword',                $metaData['format']);
		$this->assertEquals('http://www.apache.org',             $metaData['identifier']);
		$this->assertEquals('Fr',                                $metaData['language']);
		$this->assertEquals('Java, XML, XSLT, JDOM, Indexation', $metaData['subject']);
		$this->assertEquals('Tika test document',                $metaData['title']);
		$this->assertEquals('test',                              $metaData['type']);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_metadataextractionservice_testcase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_metadataextractionservice_testcase.php']);
}

?>