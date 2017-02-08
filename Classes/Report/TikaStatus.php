<?php
namespace ApacheSolrForTypo3\Tika\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Ingo Renner <ingo@typo3.org>
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

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Utility\FileUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provides a status report about whether Tika is properly configured
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class TikaStatus implements StatusProviderInterface
{

    /**
     * EXT:tika configuration.
     *
     * @var array
     */
    protected $tikaConfiguration = [];


    /**
     * Constructor, reads the extension's configuration
     *
     */
    public function __construct()
    {
        $this->tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
    }

    /**
     * Checks whether Tika is properly configured
     *
     * TODO Check whether EXT:tika is installed AFTER EXT:solr
     */
    public function getStatus()
    {
        $checks = [];
        if ($this->tikaConfiguration['extractor'] != 'solr') {
            $checks[] = $this->getJavaInstalledStatus();
        }

        switch ($this->tikaConfiguration['extractor']) {
            case 'jar':
            case 'tika': // backwards compatibility only
                $checks[] = $this->getAppConfigurationStatus();
                break;
            case 'server':
                $checks[] = $this->getServerConfigurationStatus();
                break;
            case 'solr':
                $checks[] = $this->getSolrCellConfigurationStatus();
                break;
        }

        return $checks;
    }

    /**
     * Creates a configuration OK status.
     *
     * @return Status
     */
    protected function getOkStatus()
    {
        return GeneralUtility::makeInstance(Status::class,
            'Apache Tika',
            'Configuration OK'
        );
    }

    /**
     * Creates a system status report status checking whether Java is installed.
     *
     * @return \TYPO3\CMS\Reports\Status
     */
    protected function getJavaInstalledStatus()
    {
        $status = GeneralUtility::makeInstance(Status::class,
            'Apache Tika',
            'Java OK'
        );

        if (!$this->isJavaInstalled()) {
            $status = GeneralUtility::makeInstance(Status::class,
                'Apache Tika',
                'Java Not Found',
                '<p>Please install Java.</p>',
                Status::ERROR
            );
        }

        return $status;
    }

    /**
     * Checks configuration for use with Tika app jar
     *
     * @return Status
     */
    protected function getAppConfigurationStatus()
    {
        $status = $this->getOkStatus();
        if (!$this->isFilePresent($this->tikaConfiguration['tikaPath'])) {
            $status = GeneralUtility::makeInstance(Status::class,
                'Apache Tika',
                'Configuration Incomplete',
                '<p>Could not find Tika app jar.</p>',
                Status::ERROR
            );
        }

        return $status;
    }

    /**
     * Checks configuration for use with Tika server jar
     *
     * @return Status
     */
    protected function getServerConfigurationStatus()
    {
        $status = $this->getOkStatus();

        $tikaServer = $this->getTikaServiceFromTikaConfiguration();
        if (!$tikaServer->isAvailable()) {
            $status = GeneralUtility::makeInstance(Status::class,
                'Apache Tika',
                'Configuration Incomplete',
                '<p>Could not connect to Tika server.</p>',
                Status::ERROR
            );
        }

        return $status;
    }

    /**
     * Checks configuration for use with Solr
     *
     * @return Status
     */
    protected function getSolrCellConfigurationStatus()
    {
        $status = $this->getOkStatus();

        $solrCellConfigurationOk = false;
        try {
            $solr = $this->getSolrServiceFromTikaConfiguration();

            $solr->ping();
            $plugins = $solr->getPluginsInformation();
            if (array_key_exists(
                '/update/extract',
                $plugins->plugins->QUERYHANDLER
            )) {
                $solrCellConfigurationOk = true;
            }
        } catch (\Exception $e) {
            $this->writeDevLog(
                'Exception while retrieving Solr plugin list',
                'tika',
                3,
                [
                    'configuration' => $this->tikaConfiguration,
                    'exception' => $e,
                ]
            );
        }

        if (!$solrCellConfigurationOk) {
            $status = GeneralUtility::makeInstance(Status::class,
                'Apache Tika',
                'Configuration Incomplete',
                '<p>Could not connect to Solr server.</p>',
                Status::ERROR
            );
        }

        return $status;
    }

    /**
     * @return \ApacheSolrForTypo3\Solr\SolrService
     */
    protected function getSolrServiceFromTikaConfiguration()
    {
        return GeneralUtility::makeInstance(ConnectionManager::class)->getConnection(
            $this->tikaConfiguration['solrHost'],
            $this->tikaConfiguration['solrPort'],
            $this->tikaConfiguration['solrPath'],
            $this->tikaConfiguration['solrScheme']
        );
    }

    /**
     * @return \ApacheSolrForTypo3\Tika\Service\Tika\ServerService
     */
    protected function getTikaServiceFromTikaConfiguration()
    {
        return $tikaServer = GeneralUtility::makeInstance(
            ServerService::class,
            $this->tikaConfiguration
        );
    }

    /**
     * Checks if java is installed.
     *
     * @return bool
     */
    protected function isJavaInstalled()
    {
        return CommandUtility::checkCommand('java');
    }

    /**
     * Checks if a certain file name is present.
     *
     * @param string $fileName
     * @return bool
     */
    protected function isFilePresent($fileName)
    {
        return is_file(FileUtility::getAbsoluteFilePath($fileName));
    }

    /**
     * Wrapper for GeneralUtility::devLog, used during testing to mock logging.
     *
     * @param string $message message
     * @param string $extKey extension key
     * @param int $severity severity
     * @param array $data data
     */
    protected function writeDevLog($message, $extKey, $severity = 0, $data = [])
    {
        GeneralUtility::devLog($message, $extKey, $severity, $data);
    }
}
