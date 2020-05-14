<?php
namespace ApacheSolrForTypo3\Tika\Service\Extractor;

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

use ApacheSolrForTypo3\Tika\Service\File\SizeValidator;
use ApacheSolrForTypo3\Tika\Util;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class AbstractExtractor
 *
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
abstract class AbstractExtractor implements ExtractorInterface
{

    /**
     * @var array
     */
    protected $configuration;

    /**
     * Priority in handling extraction
     *
     * @var integer
     */
    protected $priority = 0;

    /**
     * @var SizeValidator
     */
    protected $fileSizeValidator;

    /**
     * Constructor
     * @param array $extensionConfiguration
     * @param SizeValidator $fileSizeValidator
     */
    public function __construct(array $extensionConfiguration = null, SizeValidator $fileSizeValidator = null)
    {
        $this->configuration = $extensionConfiguration ?? Util::getTikaExtensionConfiguration();
        $this->fileSizeValidator = $fileSizeValidator ?? GeneralUtility::makeInstance(
            SizeValidator::class,
            $this->configuration
        );
    }

    /**
     * Returns an array of supported file types
     *
     * @return array
     */
    public function getFileTypeRestrictions()
    {
        return [];
    }

    /**
     * Get all supported DriverClasses
     *
     * @return string[] names of supported drivers/driver classes
     */
    public function getDriverRestrictions()
    {
        return [
            'Local',
        ];
    }

    /**
     * Returns the data priority of the extraction Service.
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Returns the execution priority of the extraction Service
     *
     * @return integer
     */
    public function getExecutionPriority()
    {
        return $this->priority;
    }

    /**
     * Logs a message and optionally data to devlog
     *
     * @param string $message Log message
     * @param array $data Optional data
     * @return void
     */
    protected function log($message, array $data = [])
    {
        // TODO have logger injected
        if (!$this->configuration['logging']) {
            return;
        }

        GeneralUtility::devLog($message, 'tika', 0, $data);
    }

}
