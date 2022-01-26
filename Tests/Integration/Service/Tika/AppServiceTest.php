<?php

declare(strict_types=1);
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
    protected function setUp(): void
    {
        parent::setUp();
        ExecRecorder::reset();
    }

    /**
     * @test
     */
    public function getTikaVersionUsesVParameter(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->getTikaVersion();

        self::assertContains('-V', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function extractTextUsesTParameter(): void
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractText($file);

        self::assertContains('-t', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function extractMetaDataUsesMParameter(): void
    {
        ExecRecorder::setReturnExecOutput(['foo']);
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->extractMetaData($file);

        self::assertContains('-m', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function detectLanguageFromFileUsesLParameter(): void
    {
        $file = new File(
            [
                'identifier' => 'testWORD.doc',
                'name' => 'testWORD.doc',
            ],
            $this->documentsStorageMock
        );

        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromFile($file);

        self::assertContains('-l', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function detectLanguageFromStringUsesLParameter(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromString('foo');

        self::assertContains('-l', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function callsTikaAppCorrectlyToGetMimeList(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->getSupportedMimeTypes();
        self::assertContains('--list-supported-types', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function canParseMimeList(): void
    {
        $fixtureContent = file_get_contents(__DIR__ . '/Fixtures/mimeOut');

        /** @var $service AppService */
        $service = $this->getMockBuilder(AppService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMimeTypeOutputFromTikaJar'])->getMock();
        $service->expects(self::once())->method('getMimeTypeOutputFromTikaJar')->willReturn($fixtureContent);

        $supportedMimeTypes = $service->getSupportedMimeTypes();

        self::assertContains('application/gzip', $supportedMimeTypes, 'Mimetype from listing was not found');
        self::assertContains('gzip/document', $supportedMimeTypes, 'Mimetype from alias was not found');
    }

    /**
     * @test
     */
    public function includesAdditionalCommandOptions(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->getTikaVersion();
        self::assertContains('-Dlog4j2.formatMsgNoLookups=\'true\'', ExecRecorder::$execCommand);
    }
}
