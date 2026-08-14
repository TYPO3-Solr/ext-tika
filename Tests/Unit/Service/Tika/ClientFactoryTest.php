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

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Tika;

use ApacheSolrForTypo3\Tika\Service\Tika\ClientFactory;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
use ReflectionProperty;

/**
 * Class ClientFactoryTest
 */
class ClientFactoryTest extends UnitTestCase
{
    #[Test]
    public function noAuthOptionIsSetWithoutConfiguredUsername(): void
    {
        $client = (new ClientFactory())->getClient([]);

        self::assertArrayNotHasKey('auth', $this->getClientConfig($client));
    }

    #[Test]
    public function authOptionIsSetAsUserPasswordTupleWhenUsernameIsConfigured(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerUsername' => 'tika',
            'tikaServerPassword' => 'secret',
        ]);

        self::assertSame(['tika', 'secret'], $this->getClientConfig($client)['auth']);
    }

    #[Test]
    public function authOptionUsesEmptyPasswordWhenNotConfigured(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerUsername' => 'tika',
        ]);

        self::assertSame(['tika', ''], $this->getClientConfig($client)['auth']);
    }

    #[Test]
    public function connectAndRequestTimeoutDefaultsAreUsedWhenNotConfigured(): void
    {
        $client = (new ClientFactory())->getClient([]);
        $config = $this->getClientConfig($client);

        self::assertSame(2.0, $config['connect_timeout']);
        self::assertSame(10.0, $config['timeout']);
    }

    #[Test]
    public function timeoutsAreConvertedFromMillisecondsToSeconds(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerConnectTimeoutMs' => 3000,
            'tikaServerRequestTimeoutMs' => 15000,
        ]);
        $config = $this->getClientConfig($client);

        self::assertSame(3.0, $config['connect_timeout']);
        self::assertSame(15.0, $config['timeout']);
    }

    #[Test]
    public function fractionalMillisecondsAreNotTruncated(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerConnectTimeoutMs' => 2500.5,
            'tikaServerRequestTimeoutMs' => 12345.678,
        ]);
        $config = $this->getClientConfig($client);

        self::assertSame(2.5005, $config['connect_timeout']);
        self::assertSame(12.345678, $config['timeout']);
    }

    #[Test]
    public function timeoutsAreClampedToMinimum(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerConnectTimeoutMs' => 1,
            'tikaServerRequestTimeoutMs' => 50,
        ]);
        $config = $this->getClientConfig($client);

        self::assertSame(0.1, $config['connect_timeout']);
        self::assertSame(0.1, $config['timeout']);
    }

    #[Test]
    public function timeoutsAreClampedToMaximum(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerConnectTimeoutMs' => 999999,
            'tikaServerRequestTimeoutMs' => 999999,
        ]);
        $config = $this->getClientConfig($client);

        self::assertSame(60.0, $config['connect_timeout']);
        self::assertSame(60.0, $config['timeout']);
    }

    #[Test]
    public function verifyDefaultsToTrue(): void
    {
        $client = (new ClientFactory())->getClient([]);

        self::assertTrue($this->getClientConfig($client)['verify']);
    }

    #[Test]
    public function rawGuzzleOptionsCanSetOptionsNotExposedByExtTika(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerGuzzleOptions' => [
                'verify' => false,
                'proxy' => 'http://proxy.example.com:8080',
            ],
        ]);
        $config = $this->getClientConfig($client);

        self::assertFalse($config['verify']);
        self::assertSame('http://proxy.example.com:8080', $config['proxy']);
    }

    #[Test]
    public function rawGuzzleOptionsOverruleComputedAuthAndTimeouts(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerUsername' => 'tika',
            'tikaServerPassword' => 'secret',
            'tikaServerConnectTimeoutMs' => 3000,
            'tikaServerRequestTimeoutMs' => 15000,
            'tikaServerGuzzleOptions' => [
                'auth' => ['override', 'credentials'],
                'connect_timeout' => 42.0,
                'timeout' => 99.0,
            ],
        ]);
        $config = $this->getClientConfig($client);

        self::assertSame(['override', 'credentials'], $config['auth']);
        self::assertSame(42.0, $config['connect_timeout']);
        self::assertSame(99.0, $config['timeout']);
    }

    #[Test]
    public function rawGuzzleOptionsMergeIntoNestedArraysInsteadOfReplacingThemWholesale(): void
    {
        $client = (new ClientFactory())->getClient([
            'tikaServerGuzzleOptions' => [
                'headers' => [
                    'X-Tika-Extra' => 'foo',
                ],
            ],
        ]);
        $config = $this->getClientConfig($client);

        // The default User-Agent header (set via $GLOBALS['TYPO3_CONF_VARS']['HTTP']['headers'])
        // survives, only the new header is added - a plain array_merge() would have replaced the
        // whole 'headers' array and dropped it.
        self::assertSame('TYPO3', $config['headers']['User-Agent']);
        self::assertSame('foo', $config['headers']['X-Tika-Extra']);
    }

    protected function getClientConfig(ClientInterface $client): array
    {
        $configProperty = new ReflectionProperty(Client::class, 'config');

        return $configProperty->getValue($client);
    }
}
