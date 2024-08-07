<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Service\Tika;

use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;

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

use PHPUnit\Framework\Attributes\Test;

/**
 * Class AbstractServiceTest
 */
class AbstractServiceTest extends UnitTestCase
{
    #[Test]
    public function constructorCallsInitializeService(): void
    {
        $service = $this->getMockBuilder(AbstractService::class)
            ->onlyMethods(['initializeService'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $service->expects(self::once())
            ->method('initializeService');

        $service->__construct([]);
    }
}
