<?php
namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika;

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

use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Tests\Unit\ExecRecorder;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Resource\File;


/**
 * Test case for class AppService
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
class AppServiceTest extends ServiceIntegrationTestCase
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
        $service->setLogger(new NullLogger());
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
        $service->setLogger(new NullLogger());
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
        $service->setLogger(new NullLogger());
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
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromFile($file);

        $this->assertContains('-l', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function detectLanguageFromStringUsesLParameter()
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromString('foo');

        $this->assertContains('-l', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function callsTikaAppCorrectlyToGetMimeList()
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
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
        $service = $this->getMockBuilder(AppService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMimeTypeOutputFromTikaJar'])->getMock();
        $service->expects($this->once())->method('getMimeTypeOutputFromTikaJar')->will($this->returnValue($fixtureContent));

        $supportedMimeTypes = $service->getSupportedMimeTypes();

        $this->assertContains('application/gzip', $supportedMimeTypes, 'Mimetype from listing was not found');
        $this->assertContains('gzip/document', $supportedMimeTypes, 'Mimetype from alias was not found');
    }

}

