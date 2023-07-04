<?php

declare(strict_types=1);

// adding the Solr connection status to the status report
$statusSection = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr') ? 'solr' : 'tika';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] =
    \ApacheSolrForTypo3\Tika\Report\TikaStatus::class;

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr')) {
    $tikaExtensionConfiguration = \ApacheSolrForTypo3\Tika\Util::getTikaExtensionConfiguration();
    $isSolrModuleEnabled = (is_array($tikaExtensionConfiguration)
        && isset($tikaExtensionConfiguration['showTikaSolrModule'])
        && $tikaExtensionConfiguration['showTikaSolrModule'] == 1);

    if ($isSolrModuleEnabled) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Tika',
            'searchbackend',
            'TikaControlPanel',
            'bottom',
            [
                \ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule\TikaControlPanelModuleController::class => 'index, startServer, stopServer',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:tika/Resources/Public/Images/Icons/module-tika.svg',
                'labels' => 'LLL:EXT:tika/Resources/Private/Language/locallang.xlf:solr.backend.tika.label',
            ]
        );
    }
}
