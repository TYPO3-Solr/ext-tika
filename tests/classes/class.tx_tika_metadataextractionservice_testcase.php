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
class tx_tika_MetaDataExtractionService_testcase extends tx_phpunit_testcase {

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
	public function extractsMetaDataFromBmpFile() {
		$service = t3lib_div::makeInstanceService('metaExtract', 'bmp');
		$service->setInputFile($this->testDocumentsPath . 'testBMP.bmp', 'bmp');
		$service->process();
		$metaData = $service->getOutput();

		$this->assertEquals('image/bmp', $metaData['Content-Type']);
		$this->assertEquals(75, $metaData['Height']);
		$this->assertEquals(100, $metaData['Width']);
		$this->assertEquals('testBMP.bmp', $metaData['resourceName']);
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
		$this->assertEquals('canon-55-250, moscow-birds, serbor', $metaData['fields']['keywords']);
		$this->assertArrayHasKey('copyright', $metaData['fields']);
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_metadataextractionservice_testcase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_metadataextractionservice_testcase.php']);
}

?>