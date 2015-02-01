<?php
namespace ApacheSolrForTypo3\Tika\Service\Extractor;

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

use TYPO3\CMS\Core\Resource\FileTextContentExtractorInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;


/**
 * A service to extract text from files using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
class TextExtractor implements FileTextContentExtractorInterface {

	/**
	 * @var array
	 */
	protected $configuration;

	protected $supportedFileTypes = array(
		'doc','docx','epub','htm','html','msg','odf','odt','pdf','ppt','pptx',
		'rtf','sxw','txt','xls','xlsx'
	);


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);

		if ($this->configuration['extractor'] == 'tika' && !is_file(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'], FALSE))) {
			throw new \RuntimeException(
				'Invalid path or filename for Tika application jar.',
				1266864929
			);
		}
	}

	/**
	 * Checks if the given file can be processed by this Extractor
	 *
	 * @param FileInfo $file
	 * @return bool
	 */
	public function canExtractText(FileInfo $file) {
		return in_array($file->getExtension(), $this->supportedFileTypes);
	}

	/**
	 * Extracts text from a file using Apache Tika
	 *
	 * @param FileInfo $file
	 * @return string Text extracted from the input file
	 */
	public function extractText(FileInfo $file) {
		$extractedContent = '';

		$localFilePath = $file->getPathname();
		if ($this->configuration['extractor'] == 'solr') {
			$extractedContent = $this->extractUsingSolr($localFilePath);
		} else {
			// tika || jar
			$extractedContent = $this->extractUsingTika($localFilePath);
		}

		return $extractedContent;
	}

	/**
	 * Extracts content from a given file using a local Apache Tika jar.
	 *
	 * @param string $file Absolute path to the file to extract content from.
	 * @return string Content extracted from the given file.
	 */
	protected function extractUsingTika($file) {
		$tikaCommand = CommandUtility::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'], FALSE))
			. ' -t'
			. ' ' . escapeshellarg($file);

		$shellOutput = shell_exec($tikaCommand);

		if($this->configuration['logging']){
			GeneralUtility::devLog('Text Extraction using local Tika', 'tika', 0, array(
				'file' => $file,
				'tika command' => $tikaCommand,
				'shell output' => $shellOutput
			));
		}

		return $shellOutput;
	}

	/**
	 * Extracts content from a given file using a Solr server.
	 *
	 * @param string $file Absolute path to the file to extract content from.
	 * @return string Content extracted from the given file.
	 */
	protected function extractUsingSolr($file) {
		// FIXME move connection building to EXT:solr
		// currently explicitly using "new" to bypass
		// \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance() or providing a Factory

		// EM might define a different connection than already in use by
		// Index Queue
		$solr = new \Tx_Solr_SolrService(
			$this->configuration['solrHost'],
			$this->configuration['solrPort'],
			$this->configuration['solrPath'],
			$this->configuration['solrScheme']
		);

		$query = GeneralUtility::makeInstance('tx_solr_ExtractingQuery', $file);
		$query->setExtractOnly();
		$response = $solr->extract($query);

		if ($this->configuration['logging']) {
			GeneralUtility::devLog('Text Extraction using Solr', 'tika', 0, array(
				'file' => $file,
				'solr connection' => (array)$solr,
				'query' => (array)$query,
				'response' => $response
			));
		}

		return $response[0];
	}
}
