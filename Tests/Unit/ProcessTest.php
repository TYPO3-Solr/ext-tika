<?php
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
use TYPO3\CMS\Core\Tests\UnitTestCase;


/**
 * Test case for class \ApacheSolrForTypo3\Tika\Process
 *
 */
class ProcessTest extends UnitTestCase
{

    /**
     * @test
     */
    public function constructorSetsExecutableAndArguments()
    {
        $process = new Process('foo', '-bar');

        $this->assertEquals('foo', $process->getExecutable());
        $this->assertEquals('-bar', $process->getArguments());
    }

    /**
     * @test
     */
    public function findPidUsesExecutableBasename()
    {
        $process = new Process('/usr/bin/foo', '-bar');
        ExecRecorder::setReturnExecOutput(array('foo'));

        $process->findPid();

        $this->assertTrue((bool)ExecRecorder::$execCalled);
        $this->assertContains('foo', ExecRecorder::$execCommand);
        $this->assertNotContains('/usr/bin', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function isRunningUsesPid()
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);

        $process->isRunning();

        $this->assertTrue((bool)ExecRecorder::$execCalled);
        $this->assertContains('1337', ExecRecorder::$execCommand);
    }

    /**
     * @test
     */
    public function isRunningReturnsTrueForRunningProcess()
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        ExecRecorder::setReturnExecOutput(array('1337 /usr/bin/foo -bar'));

        $running = $process->isRunning();

        $this->assertTrue($running);
    }

    /**
     * @test
     */
    public function isRunningReturnsFalseForStoppedProcess()
    {
        $process = new Process('/usr/bin/foo', '-bar');

        $running = $process->isRunning();

        $this->assertFalse($running);
    }

    /**
     * @test
     */
    public function startStartsProcess()
    {
        $process = new Process('/usr/bin/foo', '-bar');

        ExecRecorder::setReturnExecOutput(array('foo'));
        $running = $process->isRunning();
        $this->assertFalse($running);

        ExecRecorder::setReturnExecOutput(array('1337')); // runCommand() return pid of started process = 1337
        ExecRecorder::setReturnExecOutput(array('1337 /usr/bin/foo -bar')); // isRunning()
        $running = $process->start();

        $this->assertTrue($running);
    }

    /**
     * @test
     */
    public function stopStopsProcess()
    {
        $process = new Process('/usr/bin/foo', '-bar');
        $process->setPid(1337);
        ExecRecorder::setReturnExecOutput(array('1337 /usr/bin/foo -bar'));

        $running = $process->isRunning();
        $this->assertTrue($running);

        $stopped = $process->stop();
        $this->assertTrue($stopped);

        $running = $process->isRunning();
        $this->assertFalse($running);
    }

    protected function setUp()
    {
        ExecRecorder::reset();
    }

}
