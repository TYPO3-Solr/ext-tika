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

use ApacheSolrForTypo3\Tika\Service\TikaServiceFactory;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * A service to extract text from files using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
class TextExtractor implements TextExtractorInterface {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * Supported file types (by extension)
	 *
	 * @var array
	 */
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
	}

	/**
	 * Checks if the given file can be processed by this Extractor
	 *
	 * @param FileInterface $file
	 * @return bool
	 */
	public function canExtractText(FileInterface $file) {
		return in_array($file->getExtension(), $this->supportedFileTypes);
	}

	/**
	 * Extracts text from a file using Apache Tika
	 *
	 * @param FileInterface $file
	 * @return string Text extracted from the input file
	 */
	public function extractText(FileInterface $file) {
		$extractedContent = '';

		$localFilePath = $file->getForLocalProcessing();
		if ($this->configuration['extractor'] == 'solr') {
			$extractedContent = $this->extractUsingSolr($localFilePath);
		} else {
			// tika || jar
			$tika = TikaServiceFactory::getTika($this->configuration['extractor']);
			$extractedContent = $tika->extractText($file);
		}

		return $extractedContent;
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
