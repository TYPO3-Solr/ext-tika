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

namespace ApacheSolrForTypo3\Tika;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for tx_tika
 *
 * (c) dkd Internet services GmbH <info@dkd.de>
 */
class Util
{
    /**
     * Returns extension configuration.
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getTikaExtensionConfiguration(): array
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('tika');
    }

    public static function convertEnvVarStringToValue(string $value): string
    {
        if (preg_match('/%env\(([a-zA-Z0-9_]+)\)%/', $value, $matches) === 0) {
            return $value;
        }
        $resolved = getenv($matches[1]);
        if (is_string($resolved) && !empty($resolved)) {
            return $resolved;
        }

        return $value;
    }
}
