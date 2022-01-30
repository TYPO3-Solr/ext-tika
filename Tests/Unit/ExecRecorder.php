<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Tests\Unit;

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

// load the mocked functions into the namespaces which need them during tests
// include() or require() cannot load into namespaces
// so we use this little trick to achieve the effect
eval('namespace ApacheSolrForTypo3\Tika { ?>' . file_get_contents(__DIR__ . '/ExecMockFunctions.php') . ' }');
eval('namespace ApacheSolrForTypo3\Tika\Service\Tika { ?>' . file_get_contents(__DIR__ . '/ExecMockFunctions.php') . ' }');

// ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -----

namespace ApacheSolrForTypo3\Tika\Tests\Unit;

/**
 * Class ExecRecorder, holds exec() results
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ExecRecorder
{

    /**
     * Allows to capture exec() parameters
     *
     * @var string
     */
    public static string $execCommand = '';

    /**
     * Output to return to exec() calls
     *
     * @var array
     */
    public static array $execOutput = [];

    /**
     * Indicator whether/how many times the exec() mock was called.
     *
     * @var int
     */
    public static int $execCalled = 0;

    /**
     * Resets the exec() mock
     */
    public static function reset(): void
    {
        self::$execCalled = 0;
        self::$execCommand = '';
        self::$execOutput = [];
    }

    /**
     * Adds output for an exec() call.
     *
     * @param array $lines One line of returned output per element in $lines
     */
    public static function setReturnExecOutput(array $lines): void
    {
        self::$execOutput[] = $lines;
    }
}
