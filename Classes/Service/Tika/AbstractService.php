<?php
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

/**
 * Abstract Tika service implementing shared methods
 *
 * @package ApacheSolrForTypo3\Tika\Service
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
abstract class AbstractService implements ServiceInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * Logs a message and optionally data to log file
     *
     * @param string $message Log message
     * @param array $data Optional data
     * @param integer|string $severity Use constants from class LogLevel
     * @return void
     * @see LogLevel For supported log levels
     */
    protected function log(string $message, array $data = [], $severity = LogLevel::DEBUG)
    {
        if (!$this->configuration['logging']) {
            return;
        }
        $this->logger->log(
            $severity,
            $message,
            $data
        );
    }

    /**
     * @return mixed
     */
    public function getSupportedMimeTypes() {
        return [];
    }
}
