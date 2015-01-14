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

use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;


/**
 * A service to detect a text's language using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
class Language extends AbstractExtractor {

	protected $supportedFileTypes = array(
		'doc','docx','epub','htm','html','msg','odf','odt','pdf','ppt','pptx',
		'rtf','sxw','txt','xls','xlsx'
	);


	/**
	 * Checks if the given file can be processed by this Extractor
	 *
	 * @param Resource\File $file
	 * @return boolean
	 */
	public function canProcess(Resource\File $file) {
		return in_array($file->getProperty('extension'), $this->supportedFileTypes);
	}

	/**
	 * Extracts meta data from a file using Apache Tika
	 *
	 * @param Resource\File $file
	 * @param array $previousExtractedData Already extracted/existing data
	 * @return array
	 */
	public function extractMetaData(Resource\File $file, array $previousExtractedData = array()) {
		$metaData = array();

		$localFilePath = $file->getForLocalProcessing(FALSE);
		$metaData['language'] = $this->extractUsingTika($localFilePath);

		return $metaData;
	}

	/**
	 * Extracts the language from a given file using a local Apache Tika jar.
	 *
	 * @param string $file Absolute path to the file to extract meta data from.
	 * @return string Meta data extracted from the given file.
	 * @throws \RuntimeException if Java can't be found
	 */
	protected function extractUsingTika($file) {
		if (!CommandUtility::checkCommand('java')) {
			throw new \RuntimeException('Could not find Java', 1421208775);
		}

		$tikaCommand   = CommandUtility::getCommand('java')
			. ' -Dfile.encoding=UTF8'
			. ' -jar ' . escapeshellarg(GeneralUtility::getFileAbsFileName($this->configuration['tikaPath'], FALSE))
			. ' -l'
			. ' ' . escapeshellarg($file);

		$shellOutput = trim(shell_exec($tikaCommand));

		$this->log('Meta Data Extraction using local Tika', array(
			'file'         => $file,
			'tika command' => $tikaCommand,
			'shell output' => $shellOutput
		));

		return $shellOutput;
	}

}
