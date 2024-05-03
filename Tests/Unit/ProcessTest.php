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

namespace ApacheSolrForTypo3\Tika\Tests\Unit;

use ApacheSolrForTypo3\Tika\Process;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for class \ApacheSolrForTypo3\Tika\Process
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ProcessTest extends UnitTestCase
{
    protected function setUp(): void
    {
        ExecRecorder::reset();
        parent::setUp();
    }

    #[Test]
    public function constructorSetsExecutableAndArguments(): void
    {
        $process = new Process('foo', '-bar');

        self::assertEquals('foo', $process->getExecutable());
        self::assertEquals('-bar', $process->getArguments());
    }

    #[Test]
    public function findPidUsesExecutableBasename(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        ExecRecorder::setReturnExecOutput(['78986 foo']);

        $process->findPid();

        self::assertTrue((bool)ExecRecorder::$execCalled);
        self::assertStringContainsString('foo', ExecRecorder::$execCommand);
        self::assertStringNotContainsString('/usr/bin', ExecRecorder::$execCommand);
    }

    #[Test]
    public function isRunningUsesPid(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        ExecRecorder::setReturnExecOutput(['1337 foo']);

        $process->isRunning();

        self::assertTrue((bool)ExecRecorder::$execCalled);
        self::assertStringContainsString('1337', ExecRecorder::$execCommand);
    }

    #[Test]
    public function isRunningReturnsTrueForRunningProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        ExecRecorder::setReturnExecOutput(['1337 /usr/bin/foo -bar']);

        $running = $process->isRunning();

        self::assertTrue($running);
    }

    #[Test]
    public function isRunningReturnsFalseForStoppedProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');

        $running = $process->isRunning();

        self::assertFalse($running);
    }

    #[Test]
    public function startStartsProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');

        ExecRecorder::setReturnExecOutput(['foo']);
        $running = $process->isRunning();
        self::assertFalse($running);

        ExecRecorder::setReturnExecOutput(['1337']); // runCommand() return pid of started process = 1337
        ExecRecorder::setReturnExecOutput(['1337 /usr/bin/foo -bar']); // isRunning()
        $running = $process->start();

        self::assertTrue($running);
    }

    #[Test]
    public function stopStopsProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        $outputLinesForIsRunningCall = ['1337 /usr/bin/foo -bar'];

        ExecRecorder::setReturnExecOutput($outputLinesForIsRunningCall);
        $running = $process->isRunning();
        self::assertTrue($running);

        ExecRecorder::setReturnExecOutput($outputLinesForIsRunningCall);
        $stopped = $process->stop();
        self::assertTrue($stopped);

        $running = $process->isRunning();
        self::assertFalse($running);
    }
}
