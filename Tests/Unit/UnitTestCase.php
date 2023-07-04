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

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase as TYPO3TestingFrameworkUnitTestCase;

/**
 * Testcase to check if the status check returns the expected results.
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class UnitTestCase extends TYPO3TestingFrameworkUnitTestCase
{
    /**
     * Returns a mocked class where all functionality is mocked, just to fullfill the required data type
     * and to fake custom behaviour.
     *
     * @param string $className
     * @return MockObject
     */
    protected function getDumbMock(string $className): MockObject
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

    /**
     * Returns a path for a fixture.
     *
     * @param string $fixtureName
     * @return string
     * @throws string
     */
    protected function getFixturePath(string $fixtureName): string
    {
        return $this->getRuntimeDirectory() . '/Fixtures/' . $fixtureName;
    }

    /**
     * Returns the directory on runtime.
     *
     * @return string
     */
    protected function getRuntimeDirectory(): string
    {
        $rc = new ReflectionClass(static::class);
        return dirname($rc->getFileName());
    }
}
