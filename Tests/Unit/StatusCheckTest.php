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
    public function canGenerateCorrectStatusInReportForJarAppMode()
    {
        $this->fakeTikaExtensionConfigurationForExtractor('jar');

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\StatusCheck');
        $isStatusOk = $statusCheck->getStatus();

        $this->assertTrue($isStatusOk);
    }


    /**
     * @test
     */
    public function canGenerateCorrectStatusInReportForSolrMode()
    {
        $this->fakeTikaExtensionConfigurationForExtractor('solr');

        //fake existing configured extract handler
        $fakePluginStatus = new \stdClass();
        $fakePluginStatus->plugins = new \stdClass();
        $fakePluginStatus->plugins->QUERYHANDLER = array('/update/extract' => 'fake extract plugin data');

        $solrServiceMock = $this->getDumbMock('\ApacheSolrForTypo3\Solr\SolrService');
        $solrServiceMock->expects($this->once())->method('getPluginsInformation')->will($this->returnValue($fakePluginStatus));

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = $this->getMock('ApacheSolrForTypo3\\Tika\\StatusCheck',
            array('getSolrServiceFromTikaConfiguration', 'writeDevLog'));
        $statusCheck->expects($this->any())->method('getSolrServiceFromTikaConfiguration')->will($this->returnValue($solrServiceMock));
        // we expect that no devLog will be written
        $statusCheck->expects($this->never())->method('writeDevLog');

        $isStatusOk = $statusCheck->getStatus();

        $this->assertTrue($isStatusOk);
    }

    /**
     * @test
     */
    public function canWriteLogWhenExceptionIsThrownDuringRetrievalOfSolrPluginInformation()
    {
        $this->fakeTikaExtensionConfigurationForExtractor('solr');

        $solrServiceMock = $this->getDumbMock('\ApacheSolrForTypo3\Solr\SolrService');
        $solrServiceMock->expects($this->once())->method('getPluginsInformation')->will($this->throwException(
            new \Exception()
        ));

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = $this->getMock('ApacheSolrForTypo3\\Tika\\StatusCheck',
            array('getSolrServiceFromTikaConfiguration', 'writeDevLog'));
        $statusCheck->expects($this->any())->method('getSolrServiceFromTikaConfiguration')->will($this->returnValue($solrServiceMock));
        $statusCheck->expects($this->atLeastOnce())->method('writeDevLog');

        $isStatusOk = $statusCheck->getStatus();

        $this->assertFalse($isStatusOk);
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

    /**
     * @test
     */
    public function updateStatusWillUpdateRegistryWithValidCacheCommand()
    {
        $registryMock = $this->getDumbMock('TYPO3\\CMS\\Core\\Registry');
        $dataHandlerMock = $this->getDumbMock('TYPO3\CMS\Core\DataHandling\DataHandler');

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = $this->getMock('ApacheSolrForTypo3\\Tika\\StatusCheck', array('getRegistry', 'getStatus'));
        $statusCheck->expects($this->any())->method('getStatus')->will($this->returnValue(true));
        $statusCheck->expects($this->any())->method('getRegistry')->will($this->returnValue($registryMock));

        $registryMock->expects($this->exactly(1))->method('set')->with('Tx_Tika', 'available', true);

        $statusCheck->updateStatus(array('cacheCmd' => 'all'), $dataHandlerMock);
    }

    /**
     * @test
     */
    public function updateStatusWillNotUpdateRegistryWithInValidCacheCommand()
    {
        $registryMock = $this->getDumbMock('TYPO3\\CMS\\Core\\Registry');
        $dataHandlerMock = $this->getDumbMock('TYPO3\CMS\Core\DataHandling\DataHandler');

        /** @var  $statusCheck \ApacheSolrForTypo3\Tika\StatusCheck */
        $statusCheck = $this->getMock('ApacheSolrForTypo3\\Tika\\StatusCheck', array('getRegistry', 'getStatus'));
        $statusCheck->expects($this->any())->method('getStatus')->will($this->returnValue(true));
        $statusCheck->expects($this->any())->method('getRegistry')->will($this->returnValue($registryMock));

        $registryMock->expects($this->never())->method('set');

        $statusCheck->updateStatus(array('cacheCmd' => 'invalid'), $dataHandlerMock);
    }

}