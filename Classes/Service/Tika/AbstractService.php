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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;


/**
 * Abstract Tika service implementing shared methods
 *
 * @package ApacheSolrForTypo3\Tika\Service
 */
abstract class AbstractService implements ServiceInterface
{

    /**
     * @var array
     */
    protected $configuration;


    /**
     * Constructor
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->initializeService();
    }

    /**
     * Service initialization
     *
     * @return void
     */
    protected function initializeService()
    {
    }

    /**
     * Removes a temporary file.
     *
     * When working with a file, the actual file might be on a remote storage.
     * To work with it it gets copied to local storage, those temporary local
     * copies need to be removed when they're not needed anymore.
     *
     * @param string $localTempFilePath Path to the local file copy
     * @param \TYPO3\CMS\Core\Resource\FileInterface $sourceFile Original file
     */
    protected function cleanupTempFile(
        $localTempFilePath,
        FileInterface $sourceFile
    ) {
        if (PathUtility::basename($localTempFilePath) !== $sourceFile->getName()) {
            unlink($localTempFilePath);
        }
    }

    /**
     * Logs a message and optionally data to devlog
     *
     * @param string $message Log message
     * @param array $data Optional data
     * @return void
     */
    protected function log($message, array $data = array())
    {
        // TODO refactor to have logger injected
        if (!$this->configuration['logging']) {
            return;
        }

        GeneralUtility::devLog($message, 'tika', 0, $data);
    }

}
