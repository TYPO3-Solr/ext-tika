<?php

declare(strict_types=1);
namespace ApacheSolrForTypo3\Tika\Tests\Unit;

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

use ApacheSolrForTypo3\Tika\Process;

/**
 * Test case for class \ApacheSolrForTypo3\Tika\Process
 */
class ProcessTest extends UnitTestCase
{

    /**
     * @test
     */
    public function constructorSetsExecutableAndArguments(): void
    {
        $process = new Process('foo', '-bar');

        self::assertEquals('foo', $process->getExecutable());
        self::assertEquals('-bar', $process->getArguments());
    }

    /**
     * @test
     */
    public function findPidUsesExecutableBasename(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        ExecRecorder::setReturnExecOutput(['foo']);

        $process->findPid();

        self::assertTrue((bool)ExecRecorder::$execCalled);
        self::assertContains('foo', ExecRecorder::$execCommand);
        self::assertNotContains('/usr/bin', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function isRunningUsesPid(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);

        $process->isRunning();

        self::assertTrue((bool)ExecRecorder::$execCalled);
        self::assertContains('1337', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function isRunningReturnsTrueForRunningProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        ExecRecorder::setReturnExecOutput(['1337 /usr/bin/foo -bar']);

        $running = $process->isRunning();

        self::assertTrue($running);
    }

    /**
     * @test
     */
    public function isRunningReturnsFalseForStoppedProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');

        $running = $process->isRunning();

        self::assertFalse($running);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function stopStopsProcess(): void
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        ExecRecorder::setReturnExecOutput(['1337 /usr/bin/foo -bar']);

        $running = $process->isRunning();
        self::assertTrue($running);

        $stopped = $process->stop();
        self::assertTrue($stopped);

        $running = $process->isRunning();
        self::assertFalse($running);
    }

    protected function setUp(): void
    {
        ExecRecorder::reset();
    }
}
