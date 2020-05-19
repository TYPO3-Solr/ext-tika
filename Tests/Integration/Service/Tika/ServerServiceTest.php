<?php
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

use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;


/**
 * Class ServerServiceTest
 *
 */
class ServerServiceTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/tika'
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


    public function setUp()
    {
        parent::setUp();
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
        $mockedMetaDataRepository->expects($this->any())->method('findByFile')->will($this->returnValue(['file' => 1]));
        GeneralUtility::setSingletonInstance(MetaDataRepository::class, $mockedMetaDataRepository);
    }

    protected function setUpDocumentsStorageMock()
    {
        $this->testDocumentsPath = $_ENV['EXTENSION_ROOTPATH'] . 'Tests/TestDocuments/';

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

        $this->documentsStorageMock = $this->getMockBuilder(ResourceStorage::class)->setConstructorArgs([$documentsDriver, $documentsStorageRecord])->setMethods(['getUid'])->getMock();

        $this->documentsStorageMock->expects($this->any())->method('getUid')->will($this->returnValue($this->documentsStorageUid));
    }

    protected function setUpLanguagesStorageMock()
    {
        $this->testLanguagesPath = $_ENV['EXTENSION_ROOTPATH'] . 'Tests/TestLanguages/';

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


        $this->languagesStorageMock = $this->getMockBuilder(ResourceStorage::class)->setConstructorArgs([$languagesDriver, $languagesStorageRecord])->setMethods(['getUid'])->getMock();
        $this->languagesStorageMock->expects($this->any())->method('getUid')->will($this->returnValue($this->languagesStorageUid));
    }

    public function tearDown()
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
        $driver = $this->getAccessibleMock(LocalDriver::class, $mockedDriverMethods, [$driverConfiguration]);
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
     * Creates Tika Server connection configuration pointing to
     * http://localhost:9998
     *
     * @return array
     */
    protected function getTikaServerConfiguration()
    {
        return [
            'tikaServerScheme' => 'http',
            'tikaServerHost' => 'localhost',
            'tikaServerPort' => '9998'
        ];
    }


    /**
     * @test
     */
    public function extractsMetaDataFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $metaData = $service->extractMetaData($file);

        $this->assertEquals('application/msword', $metaData['Content-Type']);
        $this->assertEquals('Microsoft Office Word',
            $metaData['Application-Name']);
        $this->assertEquals('Keith Bennett', $metaData['Author']);
        $this->assertEquals('', $metaData['Company']);
        $this->assertEquals('2010-11-12T16:22:00Z', $metaData['Creation-Date']);
        $this->assertEquals('Nick Burch', $metaData['Last-Author']);
        $this->assertEquals('2010-11-12T16:22:00Z',
            $metaData['Last-Save-Date']);
        $this->assertEquals('2', $metaData['Page-Count']);
        $this->assertEquals('2', $metaData['Revision-Number']);
        $this->assertEquals('Normal.dotm', $metaData['Template']);
        $this->assertEquals('Sample Word Document', $metaData['title']);
    }

    /**
     * @test
     */
    public function extractsTextFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $expectedText = 'Sample Word Document';
        $extractedText = $service->extractText($file);

        $this->assertContains($expectedText, $extractedText);
    }

    /**
     * @test
     */
    public function extractsTextFromZipFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => 'test-documents.zip',
                'name' => 'test-documents.zip'
            ],
            $this->documentsStorageMock
        );

        $expectedTextFromWord = 'Sample Word Document';
        $extractedText = $service->extractText($file);
        $expectedTextFromPDF= 'Tika - Content Analysis Toolkit';

        $this->assertContains($expectedTextFromWord, $extractedText);
        $this->assertContains($expectedTextFromPDF, $extractedText);

    }


    /**
     * Data provider fro detectsLanguageFromFile
     *
     * @return array
     */
    public function languageFileDataProvider()
    {
        return [
            'danish' => ['da'],
            'german' => ['de'],
            'greek' => ['el'],
            'english' => ['en'],
            'spanish' => ['es'],
            'estonian' => ['et'],
            'finish' => ['fi'],
            'french' => ['fr'],
            'italian' => ['it'],
            'lithuanian' => ['lt'],
            'dutch' => ['nl'],
            'portuguese' => ['pt'],
            'swedish' => ['sv']
        ];
    }

    /**
     * @test
     * @dataProvider languageFileDataProvider
     */
    public function detectsLanguageFromFile($language)
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            [
                'identifier' => $language . '.test',
                'name' => $language . '.test'
            ],
            $this->languagesStorageMock
        );

        $detectedLanguage = $service->detectLanguageFromFile($file);

        $this->assertSame($language, $detectedLanguage);
    }

    /**
     * @test
     * @dataProvider languageFileDataProvider
     */
    public function detectsLanguageFromString($language)
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = $this->testLanguagesPath . $language . '.test';
        $languageString = file_get_contents($file);

        $detectedLanguage = $service->detectLanguageFromString($languageString);

        $this->assertSame($language, $detectedLanguage);
    }

    /**
     * @test
     */
    public function canGetMimeTypesFromServerAndParseThem()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $mimeTypes = $service->getSupportedMimeTypes();
        $this->assertContains('application/pdf', $mimeTypes, 'Server did not indicate to support pdf documents');
        $this->assertContains('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $mimeTypes, 'Server did not indicate to support docx documents');
    }


    /**
     * @test
     */
    public function canPing()
    {
        $service = new ServerService($this->getTikaServerConfiguration());
        $pingResult = $service->ping();

        $this->assertTrue($pingResult, 'Could not ping tika server');
    }

    /**
     * Avoid serialization of some properties containing objects
     *
     * @return array
     */
    public function __sleep()
    {
        $objectVars = parent::__sleep();
        unset(
            $objectVars['languagesStorageMock'],
            $objectVars['documentsStorageMock']
        );

        return $objectVars;
    }
}
