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
 * A service to extract meta data from files using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Phuong Doan <phuong.doan@dkd.de>
 * @package TYPO3
 * @subpackage tika
 */
class MetaDataExtractionService extends AbstractService {

	public $prefixId      = 'tx_tika_MetaDataExtractionService';
	public $scriptRelPath = 'Classes/Service/MetaDataExtractionService.php';
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
	 * Extracts meta data from a file using Apache Tika
	 *
	 * @param string $content Content which should be processed.
	 * @param string $type unused
	 * @param array $configuration unused
	 * @return boolean
	 */
	public function process($content = '', $type = '', $configuration = array()) {
		$this->out = array();
		$this->out['fields'] = array();

		if ($inputFile = $this->getInputFile()) {
			if ($this->tikaConfiguration['extractor'] == 'solr') {
				$metaData = $this->extractUsingSolr($inputFile);
			} else {
				$metaData = $this->extractUsingTika($inputFile);
			}

			$cleanData = $this->normalizeMetaData($metaData);
			$this->out = $cleanData;

			// DAMnizing ;)
			$this->damnizeData($cleanData);
		} else {
			$this->errorPush(T3_ERR_SV_NO_INPUT, 'No or empty input.');
		}

		return $this->getLastError();
	}

	/**
	 * Takes shell output from exec() and turns it into an array of key => value
	 * meta data pairs.
	 *
	 * @param array $shellOutputMetaData An array containing shell output from exec() with one line per entry
	 * @return array Array of key => value pairs of meta data
	 */
	protected function shellOutputToArray(array $shellOutputMetaData) {
		$metaData = array();

		foreach ($shellOutputMetaData as $line) {
			list($dataName, $dataValue) = explode(':', $line, 2);
			$metaData[$dataName] = trim($dataValue);
		}

		return $metaData;
	}

	/**
	 * Normalizes the names / keys of the meta data found.
	 *
	 * @param array $metaData An array of raw meta data from a file
	 * @return array An array with cleaned meta data keys
	 */
	protected function normalizeMetaData(array $metaData) {
		$metaDataCleaned = array();

		foreach ($metaData as $key => $value) {
			// still add the value
			$metaDataCleaned[$key] = $value;

			// clean / add values under alternative names
			switch ($key) {
				case 'Image Height':
					list($height) = explode(' ', $value, 2);
					$metaDataCleaned['height'] = $height;
					break;
				case 'Image Width':
					list($width) = explode(' ', $value, 2);
					$metaDataCleaned['width'] = $width;
					break;
				case 'Color space':
					$colorSpace = $value;
					unset($metaDataCleaned[$key]);
					$metaDataCleaned['color_space'] = $colorSpace;
					break;
				case 'Image Description':
				case 'subject':
					$description = $value;
					unset($metaDataCleaned[$key]);
					$metaDataCleaned['description'] = $description;
					break;
				case 'Headline':
					$alternative = $value;
					unset($metaDataCleaned[$key]);
					$metaDataCleaned['alternative'] = $alternative;
					break;
				case 'Keywords':
					$keywords = $value;
					unset($metaDataCleaned[$key]);
					$metaDataCleaned['keywords'] = $keywords;
					break;
			}
		}

		return $metaDataCleaned;
	}

	/**
	 * Turns the data into a format / fills the fields so that DAM can use the
	 * meta data.
	 *
	 * @param array $metaData An array with cleaned meta data keys
	 */
	protected function damnizeData(array $metaData) {
		$this->out['fields']['meta'] = $metaData;

		if ($metaData['Width']) {
			$this->out['fields']['hpixels'] = $metaData['Width'];
		}

		if ($metaData['Height']) {
			$this->out['fields']['vpixels'] = $metaData['Height'];
		}

		// JPEG comment
		if (!empty($metaData['Jpeg Comment'])) {
			$this->out['fields']['description'] = $metaData['Jpeg Comment'];
		}

		// EXIF data
		if (isset($metaData['Color Space']) && $metaData['Color Space'] != 'Undefined') {
			$this->out['fields']['color_space'] = $metaData['Color Space'];
		}

		$copyright = array();
		if (!empty($metaData['Copyright'])) {
			$copyright[] = $metaData['Copyright'];
		}
		if (!empty($metaData['Copyright Notice'])) {
			$copyright[] = $metaData['Copyright Notice'];
		}
		if (!empty($copyright)) {
			$this->out['fields']['copyright'] = implode("\n", $copyright);
		}

		if (isset($metaData['Date/Time Original'])) {
			$this->out['fields']['date_cr'] = $this->exifDateToTimestamp($metaData['Date/Time Original']);
		}

		if (isset($metaData['Keywords'])) {
			$this->out['fields']['keywords'] = implode(', ', explode(' ', $metaData['Keywords']));
		}

		if (isset($metaData['Model'])) {
			$this->out['fields']['file_creator'] = $metaData['Model'];
		}

		if (isset($metaData['X Resolution'])) {
			list($horizontalResolution) = explode(' ', $metaData['X Resolution'], 2);
			$this->out['fields']['hres'] = $horizontalResolution;
		}
		if (isset($metaData['Y Resolution'])) {
			list($verticalResolution) = explode(' ', $metaData['Y Resolution'], 2);
			$this->out['fields']['vres'] = $verticalResolution;
		}
	}

	/**
	 * Converts a date string into timestamp
	 * exiftags: 2002:09:07 15:29:52
	 *
	 * @param string $date An exif date string
	 * @return integer Unix timestamp
	 */
	protected function exifDateToTimestamp($date) {
		if (is_string($date)) {
			if (($timestamp = strtotime($date)) === -1) {
				$date = 0;
			} else {
				$date = $timestamp;
			}
		}

		return $date;
	}

	/**
	 * Extracts meta data from a given file using a local Apache Tika jar.
	 *
	 * @param string $file Absolute path to the file to extract meta data from.
	 * @return string Meta data extracted from the given file.
	 */
	protected function extractUsingTika($file) {
		$tikaCommand = CommandUtility::getCommand('java')
			. ' -Dfile.encoding=UTF8'
			. ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))
			. ' -m'
			. ' ' . escapeshellarg($file);

		$shellOutput = array();
		exec($tikaCommand, $shellOutput);
		$metaData = $this->shellOutputToArray($shellOutput);

		if ($this->tikaConfiguration['logging']) {
			GeneralUtility::devLog('Meta Data Extraction using local Tika', 'tika', 0, array(
				'file'         => $file,
				'tika command' => $tikaCommand,
				'shell output' => $shellOutput,
				'meta data'    => $metaData
			));
		}

		return $metaData;
	}

	/**
	 * Extracts meta data from a given file using a Solr server.
	 *
	 * @param  string $file Absolute path to the file to extract meta data from.
	 * @return string Meta data extracted from the given file.
	 */
	protected function extractUsingSolr($file) {
		// FIXME move connection building to EXT:solr
		// explicitly using "new" to bypass \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance() or
		// providing a Factory

		// EM might define a different connection than already in use by
		// Index Queue
		$solr = new \tx_solr_SolrService(
			$this->tikaConfiguration['solrHost'],
			$this->tikaConfiguration['solrPort'],
			$this->tikaConfiguration['solrPath'],
			$this->tikaConfiguration['solrScheme']
		);

		$query = GeneralUtility::makeInstance('tx_solr_ExtractingQuery', $file);
		$query->setExtractOnly();
		$response = $solr->extract($query);

		$metaData = $this->solrResponseToArray($response[1]);

		if ($this->tikaConfiguration['logging']) {
			GeneralUtility::devLog('Meta Data Extraction using Solr', 'tika', 0, array(
				'file'            => $file,
				'solr connection' => (array) $solr,
				'query'           => (array) $query,
				'response'        => $response,
				'meta data'       => $metaData
			));
		}

		return $metaData;
	}

	/**
	 * Turns the nested Solr response into the same format as produced by a
	 * local Tika jar call
	 *
	 * @param array $metaDataResponse The part of the Solr response containing the meta data
	 * @return array The cleaned meta data, matching the Tika jar call format
	 */
	protected function solrResponseToArray(array $metaDataResponse) {
		$cleanedData = array();

		foreach ($metaDataResponse as $dataName => $dataArray) {
			$cleanedData[$dataName] = $dataArray[0];
		}

		return $cleanedData;
	}
}
