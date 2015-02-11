<?php
namespace ApacheSolrForTypo3\Tika\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Renner <ingo@typo3.org>
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
use TYPO3\CMS\Core\Resource\File;


/**
 * A Tika service implementation using the tika-server.jar
 *
 */
class ServerService extends AbstractTikaService {

	/**
	 * Tika server URL
	 *
	 * @var string
	 */
	protected $tikaUrl;


	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();

		$this->tikaUrl = 'http://'
			. $this->configuration['tikaServerHost'] . ':'
			. $this->configuration['tikaServerPort'];
	}

	/**
	 * Takes a file reference and extracts the text from it.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string
	 */
	public function extractText(File $file) {
		// TODO: Implement extractText() method.
	}

	/**
	 * Takes a file reference and extracts its meta data.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return array
	 */
	public function extractMetaData(File $file) {
		// TODO: Implement extractMetaData() method.
	}

	/**
	 * Takes a file reference and detects its content's language.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string Language ISO code
	 */
	public function detectLanguageFromFile(File $file) {
		// TODO: Implement detectLanguageFromFile() method.
	}

	/**
	 * Takes a string as input and detects its language.
	 *
	 * @param string $input
	 * @return string Language ISO code
	 */
	public function detectLanguageFromString($input) {
		// TODO: Implement detectLanguageFromString() method.
	}
}