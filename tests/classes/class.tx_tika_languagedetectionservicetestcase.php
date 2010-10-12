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
class tx_tika_LanguageDetectionService_testcase extends tx_phpunit_testcase {

	private $testDocumentsPath;
	private $originalServices;

	public function setUp() {
			// deactivate all other services, so that we can be sure to get a
			// tika service when using makeInstanceService()

			// backup
		$this->originalServices = $GLOBALS['T3_SERVICES'];

			// deactivate all services except tika
		foreach ($GLOBALS['T3_SERVICES']['textLang'] as $serviceKey => $serviceInfo) {
			if ($serviceKey == 'tx_tika_textLang') {
				continue;
			}

			$GLOBALS['T3_SERVICES']['textLang'][$serviceKey]['available'] = FALSE;
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
	public function detectsEnglishLanguageFromString() {
		$service = t3lib_div::makeInstanceService('textLang');
		$service->process('The quick brown fox jumps over the lazy dog.');
		$language = $service->getOutput();

		$this->assertEquals('en', $language);
	}

	/**
	 * @test
	 */
	public function detectsEnglishLanguageFromFile() {
		$service = t3lib_div::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'testTXT_en.txt', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('en', $language);
	}

	/**
	 * @test
	 */
	public function detectsGermanLanguageFromString() {
		$service = t3lib_div::makeInstanceService('textLang');
		$service->process('Franz jagt im komplett verwahrlosten Taxi quer durch Bayern.');
		$language = $service->getOutput();

		$this->assertEquals('de', $language);
	}

	/**
	 * @test
	 */
	public function detectsGermanLanguageFromFile() {
		$service = t3lib_div::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'testTXT_de.txt', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('de', $language);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_languagedetectionservice_testcase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['class.tx_tika_languagedetectionservice_testcase.php']);
}

?>