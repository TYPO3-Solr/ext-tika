<?php

use ApacheSolrForTypo3\Tika\Tests\Unit\ExecRecorder;

/**
 * exec() mock to capture invocation parameters for the actual \exec() function
 *
 * @param $command
 * @param array $output
 */
function exec($command, array &$output = array())
{
    $output = ExecRecorder::$execOutput[ExecRecorder::$execCalled];
    ExecRecorder::$execCalled++;
    ExecRecorder::$execCommand = $command;
}

/**
 * shell_exec() mock to capture invocation parameters for the actual \shell_exec() function
 *
 * @param $command
 * @return string
 */
function shell_exec($command)
{
    $output = ExecRecorder::$execOutput[ExecRecorder::$execCalled];
    ExecRecorder::$execCalled++;
    ExecRecorder::$execCommand = $command;

    return $output;
}
