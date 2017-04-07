<?php
namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Tika;

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

use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Base class for EXT:tika tests
 *
 */
abstract class ServiceUnitTestCase extends UnitTestCase
{

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


    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();

        // Disable xml2array cache used by ResourceFactory
        GeneralUtility::makeInstance(CacheManager::class)->setCacheConfigurations([
            'cache_hash' => [
                'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class
            ]
        ]);

        $this->setUpDocumentsStorageMock();
        $this->setUpLanguagesStorageMock();

        $mockedMetaDataRepository = $this->getMockBuilder(MetaDataRepository::class)->getMock();
        $mockedMetaDataRepository
            ->expects($this->any())
            ->method('findByFile')
            ->will($this->returnValue(['file' => 1]));
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $mockedMetaDataRepository);
    }

    protected function setUpDocumentsStorageMock()
    {
        $this->testDocumentsPath = ExtensionManagementUtility::extPath('tika')
            . 'Tests/TestDocuments/';

        $documentsDriver = $this->createDriverFixture([
            'basePath' => $this->testDocumentsPath,
            'caseSensitive' => true
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
                'caseSensitive' => '1'
            ])
        ];

        $this->documentsStorageMock = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getUid'])
            ->setConstructorArgs([$documentsDriver, $documentsStorageRecord])
            ->getMock();
        $this->documentsStorageMock->expects($this->any())->method('getUid')->will($this->returnValue($this->documentsStorageUid));
    }

    protected function setUpLanguagesStorageMock()
    {
        $this->testLanguagesPath = ExtensionManagementUtility::extPath('tika')
            . 'Tests/TestLanguages/';

        $languagesDriver = $this->createDriverFixture([
            'basePath' => $this->testLanguagesPath,
            'caseSensitive' => true
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
                'caseSensitive' => '1'
            ])
        ];

        $this->languagesStorageMock = $this->getMockBuilder(ResourceStorage::class)
            ->setMethods(['getUid'])
            ->setConstructorArgs([$languagesDriver, $languagesStorageRecord])
            ->getMock();
        $this->languagesStorageMock->expects($this->any())
            ->method('getUid')
            ->will($this->returnValue($this->languagesStorageUid));
    }

    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * Creates a driver fixture object.
     *
     * @param array $driverConfiguration
     * @param array $mockedDriverMethods
     * @return \TYPO3\CMS\Core\Resource\Driver\LocalDriver
     */
    protected function createDriverFixture(
        array $driverConfiguration = [],
        $mockedDriverMethods = []
    ) {
        /** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $driver */
        $mockedDriverMethods[] = 'isPathValid';
        $driver = $this->getAccessibleMock(LocalDriver::class,
            $mockedDriverMethods, [$driverConfiguration]);
        $driver->expects($this->any())
            ->method('isPathValid')
            ->will(
                $this->returnValue(true)
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
                    'lDEF' => []
                ]
            ]
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
        $tikaVersion = getenv('TIKA_VERSION') ? getenv('TIKA_VERSION') : '1.10';
        $tikaPath = getenv('TIKA_PATH') ? getenv('TIKA_PATH') : '/opt/tika';

        return [
            'extractor' => '',
            'logging' => 0,

            'tikaPath' => "$tikaPath/tika-app-$tikaVersion.jar",

            'tikaServerPath' => "$tikaPath/tika-server-$tikaVersion.jar",
            'tikaServerScheme' => 'http',
            'tikaServerHost' => 'localhost',
            'tikaServerPort' => '9998',

            'solrScheme' => 'http',
            'solrHost' => 'localhost',
            'solrPort' => '8080',
            'solrPath' => '/solr/',
        ];
    }

}
