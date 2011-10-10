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
 * A service to extract text from files using Apache Tika
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class tx_tika_TextExtractionService extends t3lib_svbase {

	public $prefixId      = 'tx_tika_TextExtractionService';
	public $scriptRelPath = 'classes/class.tx_tika_textextractionservice.php';
	public $extKey        = 'tika';

	/**
	 * Holds the extension's configuration coming from the Extension Manager.
	 *
	 * @var	array
	 */
	protected $tikaConfiguration;

	/**
	 * Checks whether the service is available, reads the extension's
	 * configuration.
	 *
	 * @return	boolean	True if the service is available, false otherwise.
	 */
	public function init() {
		$available = parent::init();

		$this->tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);

		if ($this->tikaConfiguration['extractor'] == 'tika' && !is_file(t3lib_div::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))) {
			throw new Exception(
				'Invalid path or filename for tika application jar.',
				1266864929
			);
		}

		return $available;
	}

	/**
	 * Extracs text from a file using Apache Tika
	 *
	 * @param	string		Content which should be processed.
	 * @param	string		Content type
	 * @param	array		Configuration array
	 * @return	boolean
	 */
	public function process($content = '', $type = '', $configuration = array()) {
		$this->out = '';

		if ($inputFile = $this->getInputFile()) {
			if ($this->tikaConfiguration['extractor'] == 'solr') {
				$this->out = $this->extractUsingSolr($inputFile);
			} else {
				$this->out = $this->extractUsingTika($inputFile);
			}
		} else {
			$this->errorPush(T3_ERR_SV_NO_INPUT, 'No or empty input.');
		}

		return $this->getLastError();
	}

	/**
	 * Extracts content from a given file using a local Apache Tika jar.
	 *
	 * @param	string	Absolute path to the file to extract content from.
	 * @return	string	Content extracted from the given file.
	 */
	protected function extractUsingTika($file) {
		$tikaCommand = t3lib_exec::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg(t3lib_div::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))
			. ' -t ' . escapeshellarg($file);

		$shellOutput = shell_exec($tikaCommand);

		return $shellOutput;
	}

	/**
	 * Extracts content from a given file using a Solr server.
	 *
	 * @param	string	Absolute path to the file to extract content from.
	 * @return	string	Content extracted from the given file.
	 */
	protected function extractUsingSolr($file) {
			// FIXME move connection building to EXT:solr
			// currently explicitly using "new" to bypass
			// t3lib_div::makeInstance() or providing a Factory

			// EM might define a different connection than already in use by
			// Index Queue
		$solr =  new tx_solr_SolrService(
			$this->tikaConfiguration['solrHost'],
			$this->tikaConfiguration['solrPort'],
			$this->tikaConfiguration['solrPath'],
			$this->tikaConfiguration['solrScheme'],
			$this->tikaConfiguration['solrUseCurl']
		);

		$query = t3lib_div::makeInstance('tx_solr_ExtractingQuery', $file);
		$query->setExtractOnly();
		$response = $solr->extract($query);

		return $response[0];
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tika/classes/class.tx_tika_textextractionservice.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tika/classes/class.tx_tika_textextractionservice.php']);
}

?>