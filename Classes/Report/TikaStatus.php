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

use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Util;
use ApacheSolrForTypo3\Tika\Utility\FileUtility;
use Throwable;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provides a status report about whether Tika is properly configured
 *
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
        ?array $extensionConfiguration = null,
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

        try {
            switch ($this->tikaConfiguration['extractor']) {
                case 'jar':
                case 'tika': // backwards compatibility only
                    $checks[] = GeneralUtility::makeInstance(
                        Status::class,
                        'Apache Tika: Mode',
                        'App v. ' . $this->getTikaServiceFromTikaConfiguration()->getTikaVersionString(),
                        '<p>Please use Tika server instead.</p>' . PHP_EOL .
                        '<p>Don\'t forget to uninstall or at least disallow Java runtime for PHP.</p>',
                        ContextualFeedbackSeverity::WARNING,
                    );
                    $checks[] = $this->getJavaInstalledStatus();
                    $checks[] = $this->getAppConfigurationStatus();

                    break;
                case 'server':
                    $checks[] = GeneralUtility::makeInstance(
                        Status::class,
                        'Apache Tika: Mode',
                        'Tika Server v. ' . $this->getTikaServiceFromTikaConfiguration()->getTikaVersionString(),
                        '',
                        ContextualFeedbackSeverity::OK,
                    );
                    $checks[] = $this->getServerConfigurationStatus();
                    break;
            }

            $checkSecurity = true;
            foreach ($checks as $check) {
                if ($check->getTitle() === 'Apache Tika: App') {
                    continue;
                }

                if ($check->getSeverity()->value > ContextualFeedbackSeverity::OK->value) {
                    $checkSecurity = false;
                }
            }
            if ($checkSecurity) {
                $checks[] = $this->getSecurityStatus();
            }

        } catch (Throwable $e) {
            $additionalErrorInfos = /* @lang HTML */
                "
                <div class='panel panel-default'>
                    <div class='panel-heading'>
                        <h3 class='panel-title'>
                            <a href='#panel-reports-status-tika-exceptions' data-bs-toggle='collapse' class='collapsed' aria-expanded='false'>
                                Exception: \"{$e->getMessage()}\" with code {$e->getCode()} in {$e->getFile()} line {$e->getLine()}
                            </a>
                        </h3>
                    </div>

                    <div id='panel-reports-status-tika-exceptions' class='panel-collapse collapse'>
                        <div class='panel-body'>
                            {$e->getTraceAsString()}
                        </div>
                    </div>
                </div>
                ";
            $checks[] = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika: Stack-Trace',
                'Configuration incomplete or wrong',
                $additionalErrorInfos,
                ContextualFeedbackSeverity::ERROR,
            );
        }

        return $checks;
    }

    /**
     * Creates a configuration OK status.
     */
    protected function getOkStatus(?string $topic = 'Configuration'): Status
    {
        return GeneralUtility::makeInstance(
            Status::class,
            'Apache Tika: ' . $topic,
            'OK',
        );
    }

    /**
     * Creates a system status report status checking whether Java is installed.
     */
    protected function getJavaInstalledStatus(ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::ERROR): Status
    {
        $status = $this->getOkStatus('Java');

        if (!$this->isJavaInstalled()) {
            $status = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika: Java',
                'Java Not Found',
                '<p>Please install Java.</p>',
                $severity,
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
                'Apache Tika: Configuration',
                'Configuration Incomplete',
                '<p>Could not find Tika app jar.</p>',
                ContextualFeedbackSeverity::ERROR,
            );
        }

        return $status;
    }

    /**
     * Checks configuration for use with Tika server jar
     *
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    protected function getServerConfigurationStatus(): Status
    {
        $status = $this->getOkStatus();

        $tikaServer = $this->getTikaServiceFromTikaConfiguration();
        if (!$tikaServer->isAvailable()) {
            $status = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika: Configuration',
                'Configuration Incomplete',
                '<p>Could not connect to Tika server.</p>',
                ContextualFeedbackSeverity::ERROR,
            );
        }

        return $status;
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    protected function getSecurityStatus(): Status
    {
        $status = $this->getOkStatus('Security');

        $tikaService = $this->getTikaServiceFromTikaConfiguration();
        if (!$tikaService->isSecure()) {
            $status = GeneralUtility::makeInstance(
                Status::class,
                'Apache Tika: Security',
                'Vulnerable against CVE-2025-54988',
                '<p>Please update Tika to v. 3.2.3+</p>',
                ContextualFeedbackSeverity::ERROR,
            );
        }
        return $status;
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    protected function getTikaServiceFromTikaConfiguration(): AbstractService
    {
        return ServiceFactory::getConfiguredTika();
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
