<?php

/** Don't define strict_types on this place because of inclusion in {@link ExecRecorder} */

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

use ApacheSolrForTypo3\Tika\Tests\Unit\ExecRecorder;

/**
 * exec() mock to capture invocation parameters for the actual \exec() function
 *
 * @param string $command
 * @param array|null $output
 * @param int|null $result_code
 */
function exec(string $command, array &$output = null, int &$result_code = null)
{
    $output = array_key_exists(ExecRecorder::$execCalled, ExecRecorder::$execOutput) ? ExecRecorder::$execOutput[ExecRecorder::$execCalled] : '';
    ExecRecorder::$execCalled++;
    ExecRecorder::$execCommand = $command;
}

/**
 * shell_exec() mock to capture invocation parameters for the actual \shell_exec() function
 *
 * @param $command
 * @return string|false|null
 */
function shell_exec($command)
{
    $output = array_key_exists(ExecRecorder::$execCalled, ExecRecorder::$execOutput) ? ExecRecorder::$execOutput[ExecRecorder::$execCalled] : '';
    ExecRecorder::$execCalled++;
    ExecRecorder::$execCommand = $command;

    return $output;
}
