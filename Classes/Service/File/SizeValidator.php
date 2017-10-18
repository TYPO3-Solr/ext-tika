<?php

namespace ApacheSolrForTypo3\Tika\Service\File;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Timo Hund <timo.hund@dkd.de>
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
 * Class SizeValidator
 * @package ApacheSolrForTypo3\Tika\Service\File
 */
class SizeValidator {

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return bool
     */
    public function isBelowLimit(\TYPO3\CMS\Core\Resource\FileInterface $file) {
        return $file->getSize() < $this->getFileSizeLimit();
    }

    /**
     * Retrieves the size limit in byte when a text extraction on a file is done.
     *
     * Default value is 500MB.
     *
     * @return int
     */
    protected function getFileSizeLimit()
    {
        // default is 500 MB
        $bytesPerMegaByte = 1048576;
        $textExtractMegaBytes = (int)$this->getConfigurationOrDefaultValue('fileSizeLimit', 500);
        return $textExtractMegaBytes * $bytesPerMegaByte;
    }

    /**
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getConfigurationOrDefaultValue($key, $defaultValue)
    {
        return isset($this->configuration[$key]) ? $this->configuration[$key] : $defaultValue;
    }
}