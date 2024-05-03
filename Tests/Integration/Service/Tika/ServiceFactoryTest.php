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

namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika;

use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AppServiceTest
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ServiceFactoryTest extends ServiceIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->setCacheConfigurations([
            'cache_hash' => [
                'frontend' => VariableFrontend::class,
                'backend' => TransientMemoryBackend::class,
            ],
            'cache_runtime' => [
                'frontend' => VariableFrontend::class,
                'backend' => TransientMemoryBackend::class,
            ],
        ]);
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    #[Test]
    public function getTikaReturnsAppServiceForJarExtractor(): void
    {
        $extractor = ServiceFactory::getTika('jar', $this->getConfiguration());
        self::assertInstanceOf(AppService::class, $extractor);
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    #[Test]
    public function getTikaReturnsAppServiceForTikaExtractor(): void
    {
        $extractor = ServiceFactory::getTika('tika', $this->getConfiguration());
        self::assertInstanceOf(AppService::class, $extractor);
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    #[Test]
    public function getTikaReturnsServerServiceForServerExtractor(): void
    {
        $extractor = ServiceFactory::getTika('server', $this->getConfiguration());
        self::assertInstanceOf(ServerService::class, $extractor);
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    #[Test]
    public function getTikaReturnsSolrCellServiceForSolrExtractor(): void
    {
        if (!ExtensionManagementUtility::isLoaded('solr')) {
            self::markTestSkipped('EXT:solr is required for this test, but is not loaded.');
        }

        $extractor = ServiceFactory::getTika('solr', $this->getConfiguration());
        self::assertInstanceOf(SolrCellService::class, $extractor);
        self::assertInstanceOf(SolrCellService::class, $extractor);
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    #[Test]
    public function getTikaThrowsExceptionForInvalidExtractor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceFactory::getTika('foo', $this->getConfiguration());
    }
}
