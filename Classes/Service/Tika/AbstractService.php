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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * Abstract Tika service implementing shared methods
 *
 * @package ApacheSolrForTypo3\Tika\Service
 */
abstract class AbstractService implements ServiceInterface
{
    protected const JAVA_COMMAND_OPTIONS_REGEX = '/-D(?P<property>[\w.]+)=(?P<value>"[^"]+"|\'[^\']+\'|[^\\s\'"]+)/';

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
     * Logs a message and optionally data to devlog
     *
     * @param string $message Log message
     * @param array $data Optional data
     * @param integer $severity Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     * @return void
     */
    protected function log($message, array $data = [], $severity = 0)
    {
        // TODO refactor to have logger injected
        if (!$this->configuration['logging']) {
            return;
        }

        /* @var Logger $logger */
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $logger->log($severity, $message, $data);
    }

    /**
     * @return mixed
     */
    public function getSupportedMimeTypes() {
        return [];
    }

    /**
     * Parse additional Java command options.
     *
     * Reads the configuration value `javaCommandOptions` and tries to parse it to a
     * safe argument string. For safety reasons, only the following variants are
     * allowed (multiple separated by space):
     *
     * -Dfoo=bar
     * -Dfoo='hello world'
     * -Dfoo="hello world"
     *
     * @return string Parsed additional Java command options
     */
    protected function getAdditionalCommandOptions(): string
    {
        $commandOptions = trim((string)($this->configuration['javaCommandOptions'] ?? ''));

        // Early return if no additional command options are configured
        // or configuration does not match required pattern (only -D parameter is supported)
        if ('' === $commandOptions || !preg_match_all(self::JAVA_COMMAND_OPTIONS_REGEX, $commandOptions, $matches)) {
            return '';
        }

        // Combine matched command options with escaped argument value
        $commandOptionsString = '';
        foreach (array_combine($matches['property'], $matches['value']) as $property => $unescapedValue) {
            $escapedValue = CommandUtility::escapeShellArgument(trim($unescapedValue, '"\''));
            $commandOptionsString .= sprintf(' -D%s=%s', $property, $escapedValue);
        }

        return $commandOptionsString;
    }
}
