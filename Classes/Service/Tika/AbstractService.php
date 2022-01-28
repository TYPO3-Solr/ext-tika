<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Service\Tika;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * Abstract Tika service implementing shared methods
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
abstract class AbstractService implements ServiceInterface, LoggerAwareInterface
{
    protected const JAVA_COMMAND_OPTIONS_REGEX = '/-D(?P<property>[\w.]+)=(?P<value>"[^"]+"|\'[^\']+\'|[^\\s\'"]+)/';

    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected array $configuration;

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
     */
    protected function initializeService(): void
    {
    }

    /**
     * Logs a message and optionally data to log file
     *
     * @param string $message Log message
     * @param array $data Optional data
     * @param int|string $severity Use constants from class LogLevel
     * @see LogLevel For supported log levels
     */
    protected function log(string $message, array $data = [], $severity = LogLevel::DEBUG): void
    {
        if (empty($this->configuration['logging'])) {
            return;
        }
        $this->logger->log(
            $severity,
            $message,
            $data
        );
    }

    /**
     * @return array
     */
    public function getSupportedMimeTypes(): array
    {
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
