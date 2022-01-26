<?php

declare(strict_types=1);
namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika;

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

use ApacheSolrForTypo3\Tika\Util;
use function getenv;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for EXT:tika tests
 */
abstract class ServiceIntegrationTestCase extends FunctionalTestCase
{

    /**
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'SYS' =>  [
            'exceptionalErrors' =>  E_WARNING | E_RECOVERABLE_ERROR | E_DEPRECATED | E_USER_DEPRECATED,
        ],
    ];

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var string
     */
    protected $testDocumentsPath;

    /**
     * @var string
     */
    protected $testLanguagesPath;

    /**
     * @var ResourceStorage
     */
    protected $documentsStorageMock;

    /**
     * @var ResourceStorage
     */
    protected $languagesStorageMock;

    /**
     * @var int
     */
    protected $documentsStorageUid = 9000;

    /**
     * @var int
     */
    protected $languagesStorageUid = 9001;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/solr',
        'typo3conf/ext/tika',
    ];

    /**
     * Avoid serialization of some properties containing objects
     *
     * @return array
     */
    public function __sleep()
    {
        $objectVars = parent::__sleep();
        unset(
            $objectVars['documentsStorageMock'],
            $objectVars['languagesStorageMock']
        );
        return $objectVars;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->singletonInstances = GeneralUtility::getSingletonInstances();

        // Disable xml2array cache used by ResourceFactory
        GeneralUtility::makeInstance(CacheManager::class)->setCacheConfigurations([
            'cache_hash' => [
                'frontend' => VariableFrontend::class,
                'backend' => TransientMemoryBackend::class,
            ],
            'cache_runtime' => [
                'frontend' => VariableFrontend::class,
                'backend' => TransientMemoryBackend::class,
            ],
        ]);

        $this->setUpDocumentsStorageMock();
        $this->setUpLanguagesStorageMock();

        $metaDataRepositoryConstructorArgs = [];

        if (Util::getIsTYPO3VersionAbove9()) {
            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            $metaDataRepositoryConstructorArgs = [
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\EventDispatcher\EventDispatcher::class),
            ];
        }

        /* @var MetaDataRepository|MockObject $mockedMetaDataRepository */
        $mockedMetaDataRepository = $this->getMockBuilder(MetaDataRepository::class)
            ->setConstructorArgs($metaDataRepositoryConstructorArgs)
            ->getMock();
        $mockedMetaDataRepository
            ->expects(self::any())
            ->method('findByFile')
            ->willReturn(['file' => 1]);
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $mockedMetaDataRepository);
    }

    protected function setUpDocumentsStorageMock(): void
    {
        $this->testDocumentsPath = ExtensionManagementUtility::extPath('tika')
            . 'Tests/TestDocuments/';

        $documentsDriver = $this->createDriverFixture([
            'basePath' => $this->testDocumentsPath,
            'caseSensitive' => true,
        ]);

        $documentsStorageRecord = [
            'uid' => $this->documentsStorageUid,
            'is_public' => true,
            'is_writable' => false,
            'is_browsable' => true,
            'is_online' => true,
            'configuration' => $this->convertConfigurationArrayToFlexformXml([
                'basePath' => $this->testDocumentsPath,
                'pathType' => 'absolute',
                'caseSensitive' => '1',
            ]),
        ];

        $this->documentsStorageMock = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getUid'])
            ->setConstructorArgs([$documentsDriver, $documentsStorageRecord])
            ->getMock();

        $this->documentsStorageMock
            ->expects(self::any())->method('getUid')
            ->willReturn(
                $this->documentsStorageUid
            );
    }

    protected function setUpLanguagesStorageMock(): void
    {
        $this->testLanguagesPath = ExtensionManagementUtility::extPath('tika')
            . 'Tests/TestLanguages/';

        $languagesDriver = $this->createDriverFixture([
            'basePath' => $this->testLanguagesPath,
            'caseSensitive' => true,
        ]);

        $languagesStorageRecord = [
            'uid' => $this->languagesStorageUid,
            'is_public' => true,
            'is_writable' => false,
            'is_browsable' => true,
            'is_online' => true,
            'configuration' => $this->convertConfigurationArrayToFlexformXml([
                'basePath' => $this->testLanguagesPath,
                'pathType' => 'absolute',
                'caseSensitive' => '1',
            ]),
        ];

        $this->languagesStorageMock = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getUid'])
            ->setConstructorArgs([$languagesDriver, $languagesStorageRecord])
            ->getMock();
        $this->languagesStorageMock->expects(self::any())
            ->method('getUid')
            ->willReturn($this->languagesStorageUid);
    }

    protected function tearDown(): void
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * Creates a driver fixture object.
     *
     * @param array $driverConfiguration
     * @param array $mockedDriverMethods
     * @return LocalDriver
     */
    protected function createDriverFixture(
        array $driverConfiguration = [],
        $mockedDriverMethods = []
    ) {
        /** @var LocalDriver $driver */
        $mockedDriverMethods[] = 'isPathValid';
        $driver = $this->getAccessibleMock(
            LocalDriver::class,
            $mockedDriverMethods,
            [$driverConfiguration]
        );
        $driver->expects(self::any())
            ->method('isPathValid')
            ->willReturn(
                true
            );

        $driver->setStorageUid($this->documentsStorageUid);
        $driver->processConfiguration();
        $driver->initialize();
        return $driver;
    }

    /**
     * Converts a simple configuration array into a FlexForm data structure serialized as XML
     *
     * @param array $configuration
     * @return string
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml()
     */
    protected function convertConfigurationArrayToFlexformXml(
        array $configuration
    ) {
        $flexformArray = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [],
                ],
            ],
        ];
        foreach ($configuration as $key => $value) {
            $flexformArray['data']['sDEF']['lDEF'][$key] = ['vDEF' => $value];
        }
        $configuration = GeneralUtility::array2xml($flexformArray);
        return $configuration;
    }

    /**
     * Creates configuration to be used fo tests
     *
     * @return array
     */
    protected function getConfiguration()
    {
        $tikaVersion = getenv('TIKA_VERSION') ?: '1.24.1';
        $tikaPath = getenv('TIKA_PATH') ?: '/opt/tika';

        $envVarNamePrefix = 'TESTING_TIKA_';

        return [
            'extractor' => '',
            'logging' => 0,

            'tikaPath' => getenv($envVarNamePrefix . 'APP_JAR_PATH') ?: "$tikaPath/tika-app-$tikaVersion.jar",
            'javaCommandOptions' => '-Dlog4j2.formatMsgNoLookups=true',

            'tikaServerPath' => getenv($envVarNamePrefix . 'SERVER_JAR_PATH') ?: "$tikaPath/tika-server-$tikaVersion.jar",
            'tikaServerScheme' => getenv($envVarNamePrefix . 'SERVER_SCHEME') ?: 'http',
            'tikaServerHost' => getenv($envVarNamePrefix . 'SERVER_HOST') ?: 'localhost',
            'tikaServerPort' => getenv($envVarNamePrefix . 'SERVER_PORT') ?: '9998',

            'solrScheme' => getenv('TESTING_SOLR_SCHEME') ?: 'http',
            'solrHost' => getenv('TESTING_SOLR_HOST') ?: 'localhost',
            'solrPort' => getenv('TESTING_SOLR_PORT') ?: 8999,
            'solrPath' => getenv('TESTING_SOLR_PATH') ?: '/solr/core_en',
        ];
    }

    /**
     * @param array $fileData
     * @param ResourceStorage|null $storage
     * @param array $metaData
     * @return MockObject|File
     */
    protected function getMockedFileInstance(
        array $fileData,
        ResourceStorage $storage = null,
        array $metaData = []
    ) {
        if (Util::getIsTYPO3VersionBelow10()) {
            return new File($fileData, $storage ?: $this->documentsStorageMock, $metaData);
        }

        $fileMock = $this->getMockBuilder(File::class)
            ->setConstructorArgs([
                $fileData,
                $storage ?? $this->documentsStorageMock,
                $metaData,
            ])
            ->setMethods(['getMetaData'])
            ->getMock();

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$fileMock])
            ->setMethods(['get'])
            ->getMock();
        $metaDataAspectMock->expects(self::any())->method('get')->willReturn($metaData);

        $fileMock->expects(self::any())->method('getMetaData')->willReturn($metaDataAspectMock);

        return $fileMock;
    }
}
