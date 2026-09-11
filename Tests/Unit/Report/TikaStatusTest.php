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

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Report;

use ApacheSolrForTypo3\Tika\Report\TikaStatus;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use TYPO3\CMS\Reports\Status;

/**
 * Class TikaStatusTest
 */
class TikaStatusTest extends UnitTestCase
{
    /**
     * Message of the exception the mocked Tika service raises. Contains the HTML
     * metacharacters that must not reach the reports view unencoded.
     */
    private const EXCEPTION_MESSAGE = 'Tika server returned <unexpected> & "quoted" markup';

    /**
     * Builds a TikaStatus whose Tika service raises the given exception, so that
     * getStatus() renders its error panel.
     *
     * @throws MockObjectException
     */
    private function buildTikaStatusFailingWith(RuntimeException $exception): TikaStatus
    {
        /** @var TikaStatus|MockObject $tikaStatus */
        $tikaStatus = $this->getMockBuilder(TikaStatus::class)
            ->setConstructorArgs([['extractor' => 'server']])
            ->onlyMethods(['getTikaServiceFromTikaConfiguration'])
            ->getMock();
        $tikaStatus->method('getTikaServiceFromTikaConfiguration')->willThrowException($exception);

        return $tikaStatus;
    }

    /**
     * Returns the message of the stack-trace status rendered by the catch block.
     *
     * @param Status[] $checks
     */
    private function getStackTraceStatusMessage(array $checks): string
    {
        foreach ($checks as $check) {
            if ($check->getTitle() === 'Apache Tika: Stack-Trace') {
                return (string)$check->getMessage();
            }
        }
        self::fail('getStatus() did not render a stack-trace status.');
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function getStatusMustNotRenderUnencodedExceptionMessage(): void
    {
        $tikaStatus = $this->buildTikaStatusFailingWith(new RuntimeException(self::EXCEPTION_MESSAGE));

        $message = $this->getStackTraceStatusMessage($tikaStatus->getStatus());

        self::assertStringNotContainsString(
            self::EXCEPTION_MESSAGE,
            $message,
            'Exception message reached the reports view unencoded.',
        );
        self::assertStringContainsString(
            htmlspecialchars(self::EXCEPTION_MESSAGE, ENT_QUOTES),
            $message,
            'Exception message was not HTML-encoded.',
        );
    }

    /**
     * @throws MockObjectException
     */
    #[Test]
    public function getStatusMustNotRenderUnencodedStackTrace(): void
    {
        $exception = new RuntimeException(self::EXCEPTION_MESSAGE);
        $tikaStatus = $this->buildTikaStatusFailingWith($exception);

        $message = $this->getStackTraceStatusMessage($tikaStatus->getStatus());

        self::assertStringContainsString(
            htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES),
            $message,
            'Stack trace was not HTML-encoded.',
        );
    }
}
