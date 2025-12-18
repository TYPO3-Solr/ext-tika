<?php

declare(strict_types=1);

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

namespace ApacheSolrForTypo3\Tika\Service\Tika;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * Abstract Tika service implementing shared methods
 */
abstract class AbstractService implements ServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const JAVA_COMMAND_OPTIONS_REGEX = '/-D(?P<property>[\w.]+)=(?P<value>"[^"]+"|\'[^\']+\'|[^\\s\'"]+)/';

    protected array $configuration;

    /**
     * Constructor
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->initializeService();
    }

    /**
     * Service initialization
     */
    protected function initializeService(): void {}

    /**
     * Logs a message and optionally data to log file
     *
     * @param string $message Log message
     * @param array $data Optional data
     * @param int|string $severity Use constants from class LogLevel
     * @see LogLevel For supported log levels
     */
    protected function log(string $message, array $data = [], mixed $severity = LogLevel::DEBUG): void
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
        if ($commandOptions === '' || !preg_match_all(self::JAVA_COMMAND_OPTIONS_REGEX, $commandOptions, $matches)) {
            return '';
        }

        // Combine matched command options with escaped argument value
        $commandOptionsString = '';
        foreach (array_combine($matches['property'], $matches['value']) as $property => $unescapedValue) {
            $escapedValue = CommandUtility::escapeShellArgument(trim($unescapedValue ?? '', '"\''));
            $commandOptionsString .= sprintf(' -D%s=%s', $property, $escapedValue);
        }

        return $commandOptionsString;
    }

    public function getTikaVersionString(): string
    {
        $versionString = str_replace('Apache Tika', '', $this->getTikaVersion());
        $versionString = str_replace('Apache Solr', '', $versionString);
        return trim($versionString);
    }

    abstract public function getTikaVersion(): string;

    protected function applyBackwardCompatibility(array $metaData): array
    {
        $metaData['Company'] ??= '';
        $metaData['extended-properties:Company'] ??= '';

        foreach ($metaData as $key => $value) {
            if (empty($value)) {
                continue;
            }

            // add values under alternative names
            switch ($key) {
                case 'cp:revision':
                    $metaData['Revision-Number'] ??= $value;
                    break;
                case 'dc:creator':
                    $metaData['Author'] ??= $value;
                    $metaData['meta:author'] ??= $value;
                    break;

                case 'dc:description':
                case 'dc:subject':
                case 'meta:keyword':
                    $metaData['dc:subject'] ??= $value;
                    $metaData['meta:keyword'] ??= $value;
                    $metaData['Keywords'] ??= $value;
                    $metaData['cp:subject'] ??= $value;
                    $metaData['subject'] ??= $value;
                    $metaData['dc:description'] ??= $value;
                    break;
                case 'meta:page-count':
                case 'xmpTPg:NPages':
                    $metaData['meta:page-count'] ??= $value;
                    $metaData['Page-Count'] ??= $value;
                    $metaData['xmpTPg:NPages'] ??= $value;
                    break;
                case 'dc:identifier':
                    $metaData['identifier'] ??= $value;
                    break;
                case 'dc:title':
                    $metaData['title'] ??= $value;
                    break;
                case 'dc:publisher':
                    $metaData['publisher'] ??= $value;
                    break;
                case 'dcterms:created':
                case 'meta:creation-date':
                    $metaData['Creation-Date'] ??= $value;
                    $metaData['meta:creation-date'] ??= $value;
                    $metaData['date'] ??= $value;
                    break;
                case 'dcterms:modified':
                    $metaData['Last-Save-Date'] ??= $value;
                    $metaData['Last-Modified'] ??= $value;
                    $metaData['modified'] ??= $value;
                    break;
                case 'extended-properties:Application':
                    $metaData['Application-Name'] ??= $value;
                    break;
                case 'extended-properties:Company':
                    $metaData['Company'] = $metaData['Company'] ?: $value;
                    break;
                case 'extended-properties:Template':
                    $metaData['Template'] ??= $value;
                    break;
                case 'extended-properties:TotalTime':
                    $metaData['Edit-Time'] ??= $value;
                    break;
                case 'meta:last-author':
                    $metaData['Last-Author'] ??= $value;
                    break;
                case 'meta:character-count':
                    $metaData['Character Count'] ??= $value;
                    $metaData['Character-Count'] ??= $value;
                    break;
                case 'meta:save-date':
                    $metaData['Last-Save-Date'] ??= $value;
                    break;
                case 'meta:word-count':
                    $metaData['Word-Count'] ??= $value;
                    break;
                case 'w:Comments':
                    $metaData['w:comments'] ??= $value;
                    break;
                default:
                    // ignore
            }
        }

        return $metaData;
    }

    public function isSecure(): bool
    {
        if (version_compare($this->getTikaVersionString(), '3.2.2', '<')) {
            return false;
        }
        return true;
    }
}
