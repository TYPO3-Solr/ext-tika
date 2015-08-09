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

use ApacheSolrForTypo3\Tika\Service\Tika\TikaServiceFactory;
use TYPO3\CMS\Core\Resource;


/**
 * A service to detect a text's language using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
class LanguageDetector extends AbstractExtractor {

	protected $supportedFileTypes = array(
		'doc','docx','epub','htm','html','msg','odf','odt','pdf','ppt','pptx',
		'rtf','sxw','txt','xls','xlsx'
	);

	/**
	 * @var integer
	 */
	protected $priority = 98;


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

		$tika = TikaServiceFactory::getTika($this->configuration['extractor']);
		$metaData['language'] = $tika->detectLanguageFromFile($file);

		return $metaData;
	}

}
