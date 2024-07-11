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

use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase as TYPO3TestingFrameworkUnitTestCase;

/**
 * Testcase to check if the status check returns the expected results.
 */
class UnitTestCase extends TYPO3TestingFrameworkUnitTestCase
{
    /**
     * Returns a path for a fixture.
     */
    protected function getFixturePath(string $fixtureName): string
    {
        return self::getRuntimeDirectory() . '/Fixtures/' . $fixtureName;
    }

    /**
     * Returns the directory on runtime.
     */
    protected static function getRuntimeDirectory(): string
    {
        $rc = new ReflectionClass(static::class);
        return dirname($rc->getFileName());
    }
}
