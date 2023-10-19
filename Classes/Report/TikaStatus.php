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

namespace ApacheSolrForTypo3\Tika\Report;

use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use ApacheSolrForTypo3\Tika\Util;
use ApacheSolrForTypo3\Tika\Utility\FileUtility;
use Solarium\QueryType\Extract\Query;
use Throwable;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provides a status report about whether Tika is properly configured
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @copyright (c) 2010-2015 Ingo Renner <ingo@typo3.org>
 *
 * @noinspection PhpUnused Used in Reports module
 */
class TikaStatus implements StatusProviderInterface
{
    /**
     * EXT:tika configuration.
     */
    protected array $tikaConfiguration = [];

    /**
     * Constructor, reads the extension's configuration
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(
        array $extensionConfiguration = null,
    ) {
        $this->tikaConfiguration = $extensionConfiguration ?? Util::getTikaExtensionConfiguration();
    }

    public function getLabel(): string
    {
        return 'Tika';
    }

    /**
     * Checks whether Tika is properly configured
     */
    public function getStatus(): array
    {
        $checks = [];

        switch ($this->tikaConfiguration['extractor']) {
            case 'jar':
            case 'tika': // backwards compatibility only
                // for the app java is required
                $checks[] = $this->getJavaInstalledStatus();
                $checks[] = $this->getAppConfigurationStatus();

                break;
            case 'server':
                // for the server only recommended since it could also run on another endpoint
                $checks[] = $this->getJavaInstalledStatus(ContextualFeedbackSeverity::WARNING);
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
     */
    protected function getOkStatus(): Status
    {
        return GeneralUtility::makeInstance(
            Status::class,
            'Apache Tika',
            'Configuration OK'
        );
    }

    /**
     * Creates a system status report status checking whether Java is installed.
     */
    protected function getJavaInstalledStatus(ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::ERROR): Status
    {
        /** @var Status $status */
        $status = GeneralUtility::makeInstance(
            Status::class,
            'Apache Tika',
            'Java OK'
        );

        if (!$this->isJavaInstalled()) {
            $status = GeneralUtility::makeInstance(
                Status::class,
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
     */
    protected function getAppConfigurationStatus(): Status
    {
        $status = $this->getOkStatus();
        if (!$this->isFilePresent($this->tikaConfiguration['tikaPath'])) {
            $status = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika',
                'Configuration Incomplete',
                '<p>Could not find Tika app jar.</p>',
                ContextualFeedbackSeverity::ERROR
            );
        }

        return $status;
    }

    /**
     * Checks configuration for use with Tika server jar
     */
    protected function getServerConfigurationStatus(): Status
    {
        $status = $this->getOkStatus();

        $tikaServer = $this->getTikaServiceFromTikaConfiguration();
        if (!$tikaServer->isAvailable()) {
            $status = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika',
                'Configuration Incomplete',
                '<p>Could not connect to Tika server.</p>',
                ContextualFeedbackSeverity::ERROR
            );
        }

        return $status;
    }

    /**
     * Checks configuration for use with Solr
     */
    protected function getSolrCellConfigurationStatus(): Status
    {
        $status = $this->getOkStatus();

        $solrCellConfigurationOk = false;
        $additionalErrorInfos = '';
        try {
            $solrConnection = GeneralUtility::makeInstance(
                SolrCellService::class,
                $this->tikaConfiguration,
            )->getSolrConnection();

            // try to extract text & meta data
            /** @var Query $query */
            $query = GeneralUtility::makeInstance(Query::class);
            $query->setExtractOnly(true);
            $query->setFile(ExtensionManagementUtility::extPath('tika', 'ext_emconf.php'));
            $query->addParam('extractFormat', 'text');

            /** @var ResponseAdapter $response */
            [$extractedContent, $extractedMetadata, $response] = $solrConnection->getWriteService()->extractByQuery($query);

            if (!is_null($extractedContent) && !empty($extractedMetadata)) {
                $solrCellConfigurationOk = true;
            } elseif ($response instanceof ResponseAdapter) {
                $additionalErrorInfos = /* @lang HTML */
                "
                <table class='table table-condensed table-hover table-striped'>
                    <tbody>
                        <tr class='warning'>
                            <th>Status:</th><td>{$response->getHttpStatus()} {$response->getHttpStatusMessage()}</td>
                        </tr>
                        <tr class='warning'>
                            <th>Response body:</th>
                            <td>{$response->getRawResponse()}</td>
                        </tr>
                    </tbody>
                </table>
                ";
            }
        } catch (Throwable $e) {
            $additionalErrorInfos = /* @lang HTML */
                "
                <div class='panel panel-default'>
                    <div class='panel-heading'>
                        <h3 class='panel-title'>
                            <a href='#panel-reports-status-tika-solr-cell' data-bs-toggle='collapse' class='collapsed' aria-expanded='false'>
                                Exception: \"{$e->getMessage()}\" with code {$e->getCode()} in {$e->getFile()} line {$e->getLine()}
                            </a>
                        </h3>
                    </div>

                    <div id='panel-reports-status-tika-solr-cell' class='panel-collapse collapse'>
                        <div class='panel-body'>
                            {$e->getTraceAsString()}
                        </div>
                    </div>
                </div>
                ";
        }

        if (!$solrCellConfigurationOk) {
            $status = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika',
                'Configuration incomplete or wrong',
                $additionalErrorInfos,
                ContextualFeedbackSeverity::ERROR
            );
        }

        return $status;
    }

    protected function getTikaServiceFromTikaConfiguration(): ServerService
    {
        return GeneralUtility::makeInstance(
            ServerService::class,
            $this->tikaConfiguration
        );
    }

    /**
     * Checks if java is installed.
     */
    protected function isJavaInstalled(): bool
    {
        return CommandUtility::checkCommand('java');
    }

    /**
     * Checks if a certain file name is present.
     */
    protected function isFilePresent(string $fileName): bool
    {
        return is_file(FileUtility::getAbsoluteFilePath($fileName));
    }
}
