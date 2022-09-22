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

use ApacheSolrForTypo3\Tika\Util;
use InvalidArgumentException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a Tika service depending on the extension's configuration
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ServiceFactory
{
    /**
     * Creates an instance of a Tika service
     *
     * @param string $tikaServiceType Tika Service type, one of jar, server, or solr (or tika for BC, same as jar)
     * @param array|null $configuration EXT:tika EM configuration (initialized by this factory, parameter exists for tests)
     * @return AppService|ServerService|SolrCellService
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getTika(string $tikaServiceType, array $configuration = null)
    {
        if (empty($configuration)) {
            $configuration = Util::getTikaExtensionConfiguration();
        }

        switch ($tikaServiceType) {
            case 'jar':
            case 'tika': // backwards compatibility only
                return GeneralUtility::makeInstance(AppService::class, $configuration);
            case 'server':
                return GeneralUtility::makeInstance(ServerService::class, $configuration);
            case 'solr':
                return GeneralUtility::makeInstance(SolrCellService::class, $configuration);
            default:
                throw new InvalidArgumentException(
                    'Unknown Tika service type "' . $tikaServiceType . '". Must be one of jar, server, or solr.',
                    1423035119
                );
        }
    }

    /**
     * Creates a tika service instance from the extension configuration.
     *
     * @return AppService|ServerService|SolrCellService
     */
    public static function getConfiguredTika(): AbstractService
    {
        $tikaConfiguration = Util::getTikaExtensionConfiguration();
        return static::getTika($tikaConfiguration['extractor'], Util::getTikaExtensionConfiguration());
    }
}
