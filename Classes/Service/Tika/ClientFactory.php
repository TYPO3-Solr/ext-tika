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

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Builds the PSR-18 HTTP client used to talk to the Tika Server, honoring EXT:tika's own Basic-Auth
 * and timeout configuration instead of the shared TYPO3-wide client from the DI container.
 */
class ClientFactory
{
    protected const MIN_TIMEOUT_MS = 100;
    protected const MAX_TIMEOUT_MS = 60000;

    public function getClient(array $configuration): ClientInterface
    {
        $options = $GLOBALS['TYPO3_CONF_VARS']['HTTP'] ?? [];
        // The global handler stack (incl. per-context AllowedHostsMiddleware) does not apply to Tika Server requests,
        // Guzzle builds its own default stack.
        unset($options['allowed_hosts'], $options['handler']);

        if (!empty($configuration['tikaServerUsername'])) {
            $options['auth'] = [
                $configuration['tikaServerUsername'],
                (string)($configuration['tikaServerPassword'] ?? ''),
            ];
        }

        $options['connect_timeout'] = $this->getTimeoutInSeconds($configuration, 'tikaServerConnectTimeoutMs', 2000.0);
        $options['timeout'] = $this->getTimeoutInSeconds($configuration, 'tikaServerRequestTimeoutMs', 10000.0);

        // Anything configured directly in config/system/additional.php or config/system/settings.php under tikaServerGuzzleOptions
        // overrules everything above - full access to any Guzzle client option (verify, proxy, cert, ...).
        ArrayUtility::mergeRecursiveWithOverrule($options, $configuration['tikaServerGuzzleOptions'] ?? []);

        return new Client($options);
    }

    protected function getTimeoutInSeconds(array $configuration, string $key, float $defaultMs): float
    {
        $milliseconds = (float)($configuration[$key] ?? $defaultMs);
        $milliseconds = max((float)self::MIN_TIMEOUT_MS, min((float)self::MAX_TIMEOUT_MS, $milliseconds));

        return $milliseconds / 1000.0;
    }
}
