<?php
namespace ApacheSolrForTypo3\Tika\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2010-2014 Ingo Renner <ingo@typo3.org>
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

use TYPO3\CMS\Core\Service\AbstractService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;


/**
 * A service to detect a text's language using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Phuong Doan <phuong.doan@dkd.de>
 * @package TYPO3
 * @subpackage tika
 */
class LanguageDetectionService extends AbstractService {

	public $prefixId      = 'LanguageDetectionService';
	public $scriptRelPath = 'Classes/Service/LanguageDetectionService.php';
	public $extKey        = 'tika';

	/**
	 * Holds the extension's configuration coming from the Extension Manager.
	 *
	 * @var array
	 */
	protected $tikaConfiguration;


	/**
	 * Checks whether the service is available, reads the extension's
	 * configuration.
	 *
	 * @return boolean True if the service is available, false otherwise.
	 * @throws \RuntimeException if the configured Tika path is invalid
	 */
	public function init() {
		$available = parent::init();

		$this->tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);

		if ($this->tikaConfiguration['extractor'] == 'tika' && !is_file(GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))) {
			throw new \RuntimeException(
				'Invalid path or filename for tika application jar.',
				1266864929
			);
		}

		return $available;
	}

	/**
	 * Performs the language detection.
	 *
	 * @param string $content Content which should be processed.
	 * @param string $type unused
	 * @param array $configuration unused
	 * @return boolean
	 */
	public function process($content = '', $type = '', $configuration = array()) {
		$this->out = '';

		if ($content) {
			$this->setInput($content);
		}

		if ($inputFile = $this->getInputFile()) {
			if (CommandUtility::checkCommand('java')) {
				$tikaCommand = CommandUtility::getCommand('java')
					. ' -Dfile.encoding=UTF8'
					. ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))
					. ' -l'
					. ' ' . escapeshellarg($inputFile);

				$shellOutput = trim(shell_exec($tikaCommand));

				if ($this->tikaConfiguration['logging']) {
					GeneralUtility::devLog('Meta Data Extraction using local Tika', 'tika', 0, array(
						'file' => $inputFile,
						'tika command' => $tikaCommand,
						'shell output' => $shellOutput
					));
				}

				$this->out = $shellOutput;
			}
		} else {
			$this->errorPush(T3_ERR_SV_NO_INPUT, 'No or empty input.');
		}

		return $this->getLastError();
	}

}
