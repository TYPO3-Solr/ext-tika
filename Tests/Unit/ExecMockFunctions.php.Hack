<?php /** @noinspection PhpRedeclarationStdlibFunctionInspection */

/**
 * Don't define strict_types on this place because of inclusion in {@link ExecRecorder}
 *
 * Note:
 *      This file has extension ".php.Hack", to prevent PHP-Linter raise FATALs like:
 *      "PHP Fatal error:  Cannot redeclare exec() in ./Tests/Unit/ExecMockFunctions.php on line 0XX"
 *
 * If you want to edit this file, please associate this file as php in your IDE manually.
 */

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
 * @noinspection PhpUnusedParameterInspection
 */
function exec(
    string $command,
    ?array &$output = null,
    ?int &$result_code = null,
): string|false {
    $output = array_key_exists(ExecRecorder::$execCalled, ExecRecorder::$execOutput) ? ExecRecorder::$execOutput[ExecRecorder::$execCalled] : '';
    ExecRecorder::$execCalled++;
    ExecRecorder::$execCommand = $command;
    return '';
}

/**
 * shell_exec() mock to capture invocation parameters for the actual \shell_exec() function
 */
function shell_exec(string $command): string|false|null
{
    $output = array_key_exists(ExecRecorder::$execCalled, ExecRecorder::$execOutput) ? ExecRecorder::$execOutput[ExecRecorder::$execCalled] : '';
    ExecRecorder::$execCalled++;
    ExecRecorder::$execCommand = $command;

    return $output;
}
