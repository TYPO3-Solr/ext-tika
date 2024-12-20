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

use ApacheSolrForTypo3\Tika\Util;
use InvalidArgumentException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a Tika service depending on the extension's configuration
 */
class ServiceFactory
{
    /**
     * Creates an instance of a Tika service
     *
     * @param string $tikaServiceType Tika Service type, one of jar, server, or solr (or tika for BC, same as jar)
     * @param array|null $configuration EXT:tika EM configuration (initialized by this factory, parameter exists for tests)
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getTika(
        string $tikaServiceType,
        ?array $configuration = null,
    ): ServerService|AppService|SolrCellService {
        if (empty($configuration)) {
            $configuration = Util::getTikaExtensionConfiguration();
        }

        return match ($tikaServiceType) {
            'jar', 'tika' => GeneralUtility::makeInstance(AppService::class, $configuration),
            'server' => GeneralUtility::makeInstance(ServerService::class, $configuration),
            'solr' => GeneralUtility::makeInstance(SolrCellService::class, $configuration),
            default => throw new InvalidArgumentException(
                'Unknown Tika service type "' . $tikaServiceType . '". Must be one of jar, server, or solr.',
                1423035119
            ),
        };
    }

    /**
     * Creates a tika service instance from the extension configuration.
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getConfiguredTika(): ServerService|AppService|SolrCellService
    {
        $tikaConfiguration = Util::getTikaExtensionConfiguration();
        return static::getTika($tikaConfiguration['extractor'], Util::getTikaExtensionConfiguration());
    }
}
