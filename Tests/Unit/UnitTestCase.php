<?php
namespace ApacheSolrForTypo3\Tika\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Timo Schmidt <timo.schmidt@dkd.de>
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

use Nimut\TestingFramework\TestCase\UnitTestCase as TYPO3UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;

/**
 * Testcase to check if the status check returns the expected results.
 *
 * @author Timo Schmidt <timo.schmidt@dkd.de>
 * @package TYPO3
 * @subpackage tika
 */
class UnitTestCase extends TYPO3UnitTestCase
{
    /**
     * Creates configuration to be used fo tests
     *
     * @return array
     */
    protected function getConfiguration()
    {
        $tikaVersion = getenv('TIKA_VERSION') ? getenv('TIKA_VERSION') : '1.24.1';
        $tikaPath = getenv('TIKA_PATH') ? getenv('TIKA_PATH') : '/opt/tika';
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
            'solrPath' => getenv('TESTING_SOLR_PATH') ?: '/solr/'
        ];
    }

    /**
     * Returns a mocked class where all functionality is mocked, just to fullfill the required data type
     * and to fake custom behaviour.
     *
     * @param string $className
     * @return MockObject
     */
    protected function getDumbMock($className)
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
    protected function getFixturePath($fixtureName)
    {
        return $this->getRuntimeDirectory() . '/Fixtures/' . $fixtureName;
    }

    /**
     * Returns the directory on runtime.
     *
     * @return string
     * @throws ReflectionException
     */
    protected function getRuntimeDirectory()
    {
        $rc = new ReflectionClass(get_class($this));
        return dirname($rc->getFileName());
    }

}
