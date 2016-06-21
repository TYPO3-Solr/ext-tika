<?php
namespace ApacheSolrForTypo3\Tika\Service\Tika;

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

use TYPO3\CMS\Core\Resource\FileInterface;


/**
 * A common interface for the different ways of accessing Tika, e.g. app,
 * server, and Solr Cell.
 *
 * @package ApacheSolrForTypo3\Tika\Service
 */
interface ServiceInterface
{

    /**
     * Gets the Tika version
     *
     * @return string
     */
    public function getTikaVersion();

    /**
     * Takes a file reference and extracts the text from it.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file);

    /**
     * Takes a file reference and extracts its meta data.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return array
     */
    public function extractMetaData(FileInterface $file);

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file);

    /**
     * Takes a string as input and detects its language.
     *
     * @param string $input
     * @return string Language ISO code
     */
    public function detectLanguageFromString($input);

    /**
     * @return mixed
     */
    public function getSupportedMimeTypes();

    /**
     * Public method to check the availability of this service.
     *
     * @return boolean
     */
    public function isAvailable();

}
