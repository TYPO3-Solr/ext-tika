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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Class ServerServiceTest
 *
 */
class ServerServiceTest extends UnitTestCase
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


    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();

        // Disable xml2array cache used by ResourceFactory
        GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->setCacheConfigurations(array(
            'cache_hash' => array(
                'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend',
                'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend'
            )
        ));

        $this->setUpDocumentsStorageMock();
        $this->setUpLanguagesStorageMock();

        $mockedMetaDataRepository = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
        $mockedMetaDataRepository->expects($this->any())->method('findByFile')->will($this->returnValue(array('file' => 1)));
        GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository',
            $mockedMetaDataRepository);
    }

    protected function setUpDocumentsStorageMock()
    {
        $this->testDocumentsPath = ExtensionManagementUtility::extPath('tika')
            . 'Tests/TestDocuments/';

        $documentsDriver = $this->createDriverFixture(array(
            'basePath' => $this->testDocumentsPath,
            'caseSensitive' => true
        ));

        $documentsStorageRecord = array(
            'uid' => $this->documentsStorageUid,
            'is_public' => true,
            'is_writable' => false,
            'is_browsable' => true,
            'is_online' => true,
            'configuration' => $this->convertConfigurationArrayToFlexformXml(array(
                'basePath' => $this->testDocumentsPath,
                'pathType' => 'absolute',
                'caseSensitive' => '1'
            ))
        );

        $this->documentsStorageMock = $this->getMock('TYPO3\CMS\Core\Resource\ResourceStorage',
            null, array($documentsDriver, $documentsStorageRecord));
        $this->documentsStorageMock->expects($this->any())->method('getUid')->will($this->returnValue($this->documentsStorageUid));
    }

    protected function setUpLanguagesStorageMock()
    {
        $this->testLanguagesPath = ExtensionManagementUtility::extPath('tika')
            . 'Tests/TestLanguages/';

        $languagesDriver = $this->createDriverFixture(array(
            'basePath' => $this->testLanguagesPath,
            'caseSensitive' => true
        ));

        $languagesStorageRecord = array(
            'uid' => $this->languagesStorageUid,
            'is_public' => true,
            'is_writable' => false,
            'is_browsable' => true,
            'is_online' => true,
            'configuration' => $this->convertConfigurationArrayToFlexformXml(array(
                'basePath' => $this->testLanguagesPath,
                'pathType' => 'absolute',
                'caseSensitive' => '1'
            ))
        );

        $this->languagesStorageMock = $this->getMock('TYPO3\CMS\Core\Resource\ResourceStorage',
            null, array($languagesDriver, $languagesStorageRecord));
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
        $driver = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver',
            $mockedDriverMethods, array($driverConfiguration));
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
        $flexformArray = array('data' => array('sDEF' => array('lDEF' => [])));
        foreach ($configuration as $key => $value) {
            $flexformArray['data']['sDEF']['lDEF'][$key] = array('vDEF' => $value);
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
        return array(
            'tikaServerScheme' => 'http',
            'tikaServerHost' => 'localhost',
            'tikaServerPort' => '9998'
        );
    }


    /**
     * @test
     */
    public function extractsMetaDataFromDocFile()
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            array(
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ),
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
            array(
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ),
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
            array(
                'identifier' => 'test-documents.zip',
                'name' => 'test-documents.zip'
            ),
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
        return array(
            'danish' => array('da'),
            'german' => array('de'),
            'greek' => array('el'),
            'english' => array('en'),
            'spanish' => array('es'),
            'estonian' => array('et'),
            'finish' => array('fi'),
            'french' => array('fr'),
            'italian' => array('it'),
            'lithuanian' => array('lt'),
            'dutch' => array('nl'),
            'portuguese' => array('pt'),
            'swedish' => array('sv')
        );
    }

    /**
     * @test
     * @dataProvider languageFileDataProvider
     */
    public function detectsLanguageFromFile($language)
    {
        $service = new ServerService($this->getTikaServerConfiguration());

        $file = new File(
            array(
                'identifier' => $language . '.test',
                'name' => $language . '.test'
            ),
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

}
