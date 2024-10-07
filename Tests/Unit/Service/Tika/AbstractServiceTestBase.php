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

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Tika;

use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Base test class AbstractServiceTestBase
 */
abstract class AbstractServiceTestBase extends UnitTestCase
{
    protected string $serviceClass = '';

    #[Test]
    public function constructorCallsInitializeService(): void
    {
        /** @var AbstractService&MockObject $service */
        $service = $this->getMockBuilder($this->serviceClass)
            ->onlyMethods(['initializeService'])
            ->disableOriginalConstructor()
            ->getMock();

        $service->expects(self::once())
            ->method('initializeService');

        $service->__construct([]);
    }
}
