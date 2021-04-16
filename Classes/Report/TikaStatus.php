<?php
namespace ApacheSolrForTypo3\Tika\Report;

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

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Util;
use ApacheSolrForTypo3\Tika\Utility\FileUtility;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Solarium\QueryType\Extract\Query;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Provides a status report about whether Tika is properly configured
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @copyright (c) 2010-2015 Ingo Renner <ingo@typo3.org>
 */
class TikaStatus implements StatusProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * EXT:tika configuration.
     *
     * @var array
     */
    protected $tikaConfiguration = [];

    /**
     * Constructor, reads the extension's configuration
     * @param array|null $extensionConfiguration
     */
    public function __construct(array $extensionConfiguration = null)
    {
        $this->tikaConfiguration = $extensionConfiguration ?? Util::getTikaExtensionConfiguration();
    }

    /**
     * Checks whether Tika is properly configured
     *
     * TODO Check whether EXT:tika is installed AFTER EXT:solr
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function getStatus()
    {
        $checks = [];

        switch ($this->tikaConfiguration['extractor']) {
            case 'jar':
            case 'tika': // backwards compatibility only
                // for the app java is required
                $checks[] = $this->getJavaInstalledStatus(Status::ERROR);
                $checks[] = $this->getAppConfigurationStatus();

                break;
            case 'server':
                // for the server only recommended since it could also run on another node
                $checks[] = $this->getJavaInstalledStatus(Status::WARNING);
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
     * @noinspection PhpIncompatibleReturnTypeInspection
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
     * @param integer $severity
     * @return Status
     */
    protected function getJavaInstalledStatus($severity = Status::ERROR)
    {
        /* @var Status $status */
        $status = GeneralUtility::makeInstance(Status::class,
            'Apache Tika',
            'Java OK'
        );

        if (!$this->isJavaInstalled()) {
            $status = GeneralUtility::makeInstance(Status::class,
                'Apache Tika',
                'Java Not Found',
                '<p>Please install Java.</p>',
                $severity
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
     * @throws Exception
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
            $solr = $this->getSolrConnectionFromTikaConfiguration();

            // try to extract text & meta data
            /** @var $query Query */
            $query = GeneralUtility::makeInstance(Query::class);
            $query->setExtractOnly(true);
            $query->setFile(ExtensionManagementUtility::extPath('tika', 'ext_emconf.php'));
            $query->addParam('extractFormat', 'text');

            list($extractedContent, $extractedMetadata) = $solr->getWriteService()->extractByQuery($query);

            if (!is_null($extractedContent) && !empty($extractedMetadata)) {
                $solrCellConfigurationOk = true;
            }
        } catch (Exception $e) {
            $this->writeDevLog(
                'Exception while trying to extract file content',
                'tika',
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
                '<p>Could not extract file contents with Solr Cell.</p>',
                Status::ERROR
            );
        }

        return $status;
    }

    /**
     * @return SolrConnection
     */
    protected function getSolrConnectionFromTikaConfiguration()
    {
        $solrConfig = [
            'host' => $this->tikaConfiguration['solrHost'],
            'port' => $this->tikaConfiguration['solrPort'],
            'path' => $this->tikaConfiguration['solrPath'],
            'scheme' => $this->tikaConfiguration['solrScheme']
        ];

        $config = [
            'read' => $solrConfig,
            'write' => $solrConfig
        ];
        return GeneralUtility::makeInstance(ConnectionManager::class)->getConnectionFromConfiguration($config);
    }

    /**
     * @return ServerService
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function getTikaServiceFromTikaConfiguration()
    {
        return GeneralUtility::makeInstance(
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
     * @param array $data data
     */
    protected function writeDevLog(string $message, string $extKey, array $data = [])
    {
        $this->logger->debug(
            $message,
            [
                'extension' => $extKey,
                'data' => $data
            ]
        );
    }
}
