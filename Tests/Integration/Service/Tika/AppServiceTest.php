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
use ApacheSolrForTypo3\Tika\Tests\Unit\ExecRecorder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Resource\File;

/**
 * Test case for class AppService
 *
 * @author Ingo Renner <ingo@typo3.org>
 *
 * @todo: Move all *Parameter() methods to Unit, to speedup the tests.
 */
class AppServiceTest extends ServiceIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ExecRecorder::reset();
    }

    #[Test]
    public function getTikaVersionUsesVParameter(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->getTikaVersion();

        self::assertStringContainsString('-V', ExecRecorder::$execCommand);
    }

    #[Test]
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

        self::assertStringContainsString('-t', ExecRecorder::$execCommand);
    }

    #[Test]
    public function extractMetaDataUsesMParameter(): void
    {
        ExecRecorder::setReturnExecOutput(['foo : bar']);
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

        self::assertStringContainsString('-m', ExecRecorder::$execCommand);
    }

    #[Test]
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

        self::assertStringContainsString('-l', ExecRecorder::$execCommand);
    }

    #[Test]
    public function detectLanguageFromStringUsesLParameter(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->detectLanguageFromString('foo');

        self::assertStringContainsString('-l', ExecRecorder::$execCommand);
    }

    #[Test]
    public function callsTikaAppCorrectlyToGetMimeList(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->getSupportedMimeTypes();
        self::assertStringContainsString('--list-supported-types', ExecRecorder::$execCommand);
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function canParseMimeList(): void
    {
        $fixtureContent = file_get_contents(__DIR__ . '/Fixtures/mimeOut');

        /** @var AppService|MockObject $service */
        $service = $this->createPartialMock(
            AppService::class,
            [
                'getMimeTypeOutputFromTikaJar',
            ]
        );
        $service->expects(self::once())->method('getMimeTypeOutputFromTikaJar')->willReturn($fixtureContent);

        $supportedMimeTypes = $service->getSupportedMimeTypes();

        self::assertContains('application/gzip', $supportedMimeTypes, 'Mimetype from listing was not found');
        self::assertContains('gzip/document', $supportedMimeTypes, 'Mimetype from alias was not found');
    }

    #[Test]
    public function includesAdditionalCommandOptions(): void
    {
        $service = new AppService($this->getConfiguration());
        $service->setLogger(new NullLogger());
        $service->getTikaVersion();
        self::assertStringContainsString('-Dlog4j2.formatMsgNoLookups=\'true\'', ExecRecorder::$execCommand);
    }
}
