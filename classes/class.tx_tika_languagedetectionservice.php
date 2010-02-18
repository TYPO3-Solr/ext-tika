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
 * A service to detect a text's language using Apache Tika
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class tx_tika_LanguageDetectionService extends t3lib_svbase {

	public $prefixId      = 'tx_tika_LanguageDetectionService'; // Same as class name
	public $scriptRelPath = 'classes/class.tx_tika_languagedetectionservice.php'; // Path to this script relative to the extension dir.
	public $extKey        = 'tika'; // The extension key.

	/**
	 * @var	array
	 */
	protected $configuration;

	/**
	 * "Constructor"
	 *
	 * @return	boolean	TRUE if the service is available, FALSE otherwise
	 */
	public function init() {
		$available = parent::init();

		$this->configuration = unserialize(
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']
		);

			// checking for proper Java and Tika configuration
		if (empty($this->configuration['pathJava']) || !is_file($this->configuration['pathJava'])) {
			$available = FALSE;
		}

		if (empty($this->configuration['pathTika']) || !is_file($this->configuration['pathTika'])) {
			$available = FALSE;
		}

		if (!$available) {
			$registry = t3lib_div::makeInstance('t3lib_Registry');
			$registry->set('tx_tika', 'availability.tika', FALSE);
		}

		return $available;
	}

	/**
	 * Performs the language detection.
	 *
	 * @param	string 	Content which should be processed.
	 * @param	string 	unused
	 * @param	array 	Configuration array
	 * @return	boolean
	 */
	public function process($content = '', $type = '', $configuration = array()) {

		// Depending on the service type there's not a process() function.
		// You have to implement the API of that service type.

		return FALSE;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tika/classes/class.tx_tika_languagedetectionservice.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tika/classes/class.tx_tika_languagedetectionservice.php']);
}

?>