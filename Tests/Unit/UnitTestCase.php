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
     * Creates configuration to be used fo tests
     *
     * @return array
     */
    protected function getConfiguration(): array
    {
        $tikaVersion = getenv('TIKA_VERSION') ?: '1.24.1';
        $tikaPath = getenv('TIKA_PATH') ?: '/opt/tika';
        $envVarNamePrefix = 'TESTING_TIKA_';

        return [
            'extractor' => '',
            'logging' => 0,

            'tikaPath' => "$tikaPath/tika-app-$tikaVersion.jar",

            'tikaServerPath' => "$tikaPath/tika-server-$tikaVersion.jar",
            'tikaServerScheme' => getenv($envVarNamePrefix . 'SERVER_SCHEME') ?: 'http',
            'tikaServerHost' => getenv($envVarNamePrefix . 'SERVER_HOST') ?: 'localhost',
            'tikaServerPort' => getenv($envVarNamePrefix . 'SERVER_PORT') ?: '9998',

            'solrScheme' => getenv('TESTING_SOLR_SCHEME') ?: 'http',
            'solrHost' => getenv('TESTING_SOLR_HOST') ?: 'localhost',
            /*
             * TODO: The port number differs that is in use for the integration test
             *       This needs to be checked
             * @see \ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika\ServiceIntegrationTestCase::getConfiguration
             */
            'solrPort' => getenv('TESTING_SOLR_PORT') ?: 8080,
            'solrPath' => getenv('TESTING_SOLR_PATH') ?: '/solr/',
        ];
    }

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
        $rc = new ReflectionClass(get_class($this));
        return dirname($rc->getFileName());
    }
}
