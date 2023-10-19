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

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use ReflectionObject;
use RuntimeException;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\MetaDataAspect;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

use function getenv;

/**
 * Base class for EXT:tika tests
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
abstract class ServiceIntegrationTestCase extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' =>  [
            'exceptionalErrors' =>  E_WARNING | E_RECOVERABLE_ERROR | E_DEPRECATED | E_USER_DEPRECATED,
        ],
    ];

    /**
     * @var array A backup of registered singleton instances
     */
    protected array $singletonInstances = [];

    protected string $testDocumentsPath;

    protected string $testLanguagesPath;

    protected ResourceStorage $documentsStorageMock;

    protected ResourceStorage $languagesStorageMock;

    protected int $documentsStorageUid = 9000;

    protected int $languagesStorageUid = 9001;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/solr',
        'typo3conf/ext/tika',
    ];

    /**
     * Avoid serialization of some properties containing objects
     */
    public function __sleep()
    {
        $objectVars = get_object_vars($this);
        unset(
            $objectVars['documentsStorageMock'],
            $objectVars['languagesStorageMock']
        );
        return array_keys($objectVars);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->singletonInstances = GeneralUtility::getSingletonInstances();

        // Disable xml2array cache used by ResourceFactory
        GeneralUtility::makeInstance(CacheManager::class)->setCacheConfigurations([
            'hash' => [
                'frontend' => VariableFrontend::class,
                'backend' => TransientMemoryBackend::class,
            ],
            'runtime' => [
                'frontend' => VariableFrontend::class,
                'backend' => TransientMemoryBackend::class,
            ],
        ]);

        $this->setUpDocumentsStorageMock();
        $this->setUpLanguagesStorageMock();

        $metaDataRepositoryConstructorArgs = [
            GeneralUtility::makeInstance(EventDispatcher::class),
        ];

        /** @var MetaDataRepository|MockObject $mockedMetaDataRepository */
        $mockedMetaDataRepository = $this->getMockBuilder(MetaDataRepository::class)
            ->setConstructorArgs($metaDataRepositoryConstructorArgs)
            ->getMock();
        $mockedMetaDataRepository
            ->expects(self::any())
            ->method('findByFile')
            ->willReturn(['file' => 1]);
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $mockedMetaDataRepository);
        // Set $GLOBALS['TYPO3_CONF_VARS'], to avoid PHP 8.0+ warning like "Undefined global variable"
        $GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function'] = false;
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
            ->onlyMethods(['getUid'])
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
            ->onlyMethods(['getUid'])
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
        array $mockedDriverMethods = []
    ): LocalDriver {
        $mockedDriverMethods[] = 'isPathValid';
        /** @var LocalDriver|MockObject $driver */
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
     * @see {@link GeneralUtility::array2xml}
     */
    protected function convertConfigurationArrayToFlexformXml(
        array $configuration
    ): string {
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
        return GeneralUtility::array2xml($flexformArray);
    }

    /**
     * Creates configuration to be used fo tests
     *
     * @return array
     */
    protected function getConfiguration(): array
    {
        $tikaComposerManifest = json_decode(file_get_contents(GeneralUtility::getFileAbsFileName('EXT:tika/composer.json')), true);
        $tikaVersion = $tikaComposerManifest['extra']['TYPO3-Solr']['ext-tika']['require']['Tika'];
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
            'solrPath' => getenv('TESTING_SOLR_PATH') ?: '/',
            'solrCore' => getenv('TESTING_SOLR_CORE') ?: 'core_en',
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
    ): File|MockObject {
        $fileMock = $this->getMockBuilder(File::class)
            ->setConstructorArgs([
                $fileData,
                $storage ?? $this->documentsStorageMock,
                $metaData,
            ])
            ->onlyMethods(['getMetaData'])
            ->getMock();

        $metaDataAspectMock = $this->getMockBuilder(MetaDataAspect::class)
            ->setConstructorArgs([$fileMock])
            ->onlyMethods(['get'])
            ->getMock();
        $metaDataAspectMock->expects(self::any())->method('get')->willReturn($metaData);

        $fileMock->expects(self::any())->method('getMetaData')->willReturn($metaDataAspectMock);

        return $fileMock;
    }

    /*
        Nimut testing framework goodies, copied from https://github.com/Nimut/testing-framework
     */

    /**
     * Injects $dependency into property $name of $target
     *
     * This is a convenience method for setting a protected or private property in
     * a test subject for the purpose of injecting a dependency.
     *
     * Copied from https://github.com/Nimut/testing-framework/blob/3d0573b23fe16157460b4e73e51e1cc0903ea35c/src/TestingFramework/TestCase/AbstractTestCase.php#L247-L284
     *
     * @param object $target The instance which needs the dependency
     * @param string $name Name of the property to be injected
     * @param mixed $dependency The dependency to inject – usually an object but can also be any other type
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function inject(object $target, string $name, mixed $dependency): void
    {
        if (!is_object($target)) {
            throw new InvalidArgumentException('Wrong type for argument $target, must be object.', 1476107338);
        }

        $objectReflection = new ReflectionObject($target);
        $methodNamePart = strtoupper($name[0]) . substr($name, 1);
        if ($objectReflection->hasMethod('set' . $methodNamePart)) {
            $methodName = 'set' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
            $methodName = 'inject' . $methodNamePart;
            $target->$methodName($dependency);
        } elseif ($objectReflection->hasProperty($name)) {
            $property = $objectReflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue($target, $dependency);
        } else {
            throw new RuntimeException(
                'Could not inject ' . $name . ' into object of type ' . get_class($target),
                1476107339
            );
        }
    }

    /**
     * Helper function to call protected or private methods
     *
     * Copied from https://github.com/Nimut/testing-framework/blob/3d0573b23fe16157460b4e73e51e1cc0903ea35c/src/TestingFramework/TestCase/AbstractTestCase.php#L227-L245
     *
     * @param object $object The object to be invoked
     * @param string $name the name of the method to call
     * @return mixed
     * @throws ReflectionException
     */
    protected function callInaccessibleMethod(object $object, string $name): mixed
    {
        // Remove first two arguments ($object and $name)
        $arguments = func_get_args();
        array_splice($arguments, 0, 2);

        $reflectionObject = new ReflectionObject($object);
        $reflectionMethod = $reflectionObject->getMethod($name);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $arguments);
    }
}
