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

use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Tests\Unit\ExecRecorder;
use TYPO3\CMS\Core\Resource\File;


/**
 * Test case for class AppService
 *
 */
class AppServiceTest extends ServiceUnitTestCase
{

    protected function setUp()
    {
        parent::setUp();
        ExecRecorder::reset();
    }

    /**
     * @test
     */
    public function getTikaVersionUsesVParameter()
    {
        $service = new AppService($this->getConfiguration());
        $service->getTikaVersion();

        $this->assertContains('-V', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function extractTextUsesTParameter()
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service = new AppService($this->getConfiguration());
        $service->extractText($file);

        $this->assertContains('-t', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function extractMetaDataUsesMParameter()
    {
        ExecRecorder::setReturnExecOutput(['foo']);
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service = new AppService($this->getConfiguration());
        $service->extractMetaData($file);

        $this->assertContains('-m', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function detectLanguageFromFileUsesLParameter()
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc'
            ],
            $this->documentsStorageMock
        );

        $service = new AppService($this->getConfiguration());
        $service->detectLanguageFromFile($file);

        $this->assertContains('-l', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function detectLanguageFromStringUsesLParameter()
    {
        $service = new AppService($this->getConfiguration());
        $service->detectLanguageFromString('foo');

        $this->assertContains('-l', ExecRecorder::$execCommand);
    }


    /**
     * @test
     */
    public function callsTikaAppCorrectlyToGetMimeList()
    {
        $service = new AppService($this->getConfiguration());
        $service->getSupportedMimeTypes();
        $this->assertContains('--list-supported-types', ExecRecorder::$execCommand);
    }


    /**
     * @test
     */
    public function canParseMimeList()
    {
        $fixtureContent = file_get_contents(dirname(__FILE__) . '/Fixtures/mimeOut');

        /** @var $service AppService */
        $service = $this->getMockBuilder(AppService::class)->disableOriginalConstructor()->setMethods(['getMimeTypeOutputFromTikaJar'])->getMock();
        $service->expects($this->once())->method('getMimeTypeOutputFromTikaJar')->will($this->returnValue($fixtureContent));

        $supportedMimeTypes = $service->getSupportedMimeTypes();

        $this->assertContains('application/gzip', $supportedMimeTypes, 'Mimetype from listing was not found');
        $this->assertContains('gzip/document', $supportedMimeTypes, 'Mimetype from alias was not found');
    }

}

