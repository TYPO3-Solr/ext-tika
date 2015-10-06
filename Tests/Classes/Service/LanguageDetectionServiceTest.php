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
 * Unit tests for the meta data extraction service
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class LanguageDetectionServiceTest {

	private $testDocumentsPath;
	private $originalServices;

	public function setUp() {
			// deactivate all other services, so that we can be sure to get a
			// tika service when using makeInstanceService()

			// backup
		$this->originalServices = $GLOBALS['T3_SERVICES'];

			// deactivate all services except tika
		foreach ($GLOBALS['T3_SERVICES']['textLang'] as $serviceKey => $serviceInfo) {
			if ($serviceKey == 'Tx_Tika_TextLang') {
				continue;
			}

			$GLOBALS['T3_SERVICES']['textLang'][$serviceKey]['available'] = FALSE;
		}

		$this->testDocumentsPath = ExtensionManagementUtility::extPath('tika') . 'Tests/TestLanguages/';
	}

	public function tearDown() {
			// restore services
		$GLOBALS['T3_SERVICES'] = $this->originalServices;
	}

	/**
	 * @test
	 */
	public function detectsDanishLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'da.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('da', $language);
	}

	/**
	 * @test
	 */
	public function detectsDutchLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'nl.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('nl', $language);
	}

	/**
	 * @test
	 */
	public function detectsEnglishLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'en.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('en', $language);
	}

	/**
	 * @test
	 */
	public function detectsFinnishLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'fi.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('fi', $language);
	}

	/**
	 * @test
	 */
	public function detectsFrenchLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'fr.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('fr', $language);
	}

	/**
	 * @test
	 */
	public function detectsGermanLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'de.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('de', $language);
	}

	/**
	 * @test
	 */
	public function detectsItalianLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'it.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('it', $language);
	}

	/**
	 * @test
	 */
	public function detectsPortugueseLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'pt.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('pt', $language);
	}

	/**
	 * @test
	 */
	public function detectsSpanishLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'es.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('es', $language);
	}

	/**
	 * @test
	 */
	public function detectsSwedishLanguage() {
		$service = GeneralUtility::makeInstanceService('textLang');
		$service->setInputFile($this->testDocumentsPath . 'sv.test', 'txt');
		$service->process();
		$language = $service->getOutput();

		$this->assertEquals('sv', $language);
	}
}
