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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;


/**
 * Testcase to check if the status check returns the expected results.
 *
 * @author Timo Schmidt <timo.schmidt@dkd.de>
 * @package TYPO3
 * @subpackage tika
 */
class StatusCheckTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canGenerateCorrectStatusInReportForServerMode()
    {
        $this->fakeTikaExtensionConfigurationForExtractor('server');

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\StatusCheck');
        $isStatusOk = $statusCheck->getStatus();

        $this->assertTrue($isStatusOk);
    }

    /**
     * @test
     */
    public function canGenerateCorrectStatusInReportForJarAppModeMode()
    {
        $this->fakeTikaExtensionConfigurationForExtractor('jar');

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\StatusCheck');
        $isStatusOk = $statusCheck->getStatus();

        $this->assertTrue($isStatusOk);
    }

    /**
     * Fakes an existing extension configuration in $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']
     * configured for the ci environment.
     *
     * @param string $extractorName
     */
    protected function fakeTikaExtensionConfigurationForExtractor($extractorName)
    {
        $configuration = $this->getConfiguration();
        $configuration['extractor'] = $extractorName;
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika'] = serialize($configuration);
    }
}