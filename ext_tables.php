<?php
// Prevent Script from beeing called directly
defined('TYPO3_MODE') || die();

// adding the Solr connection status to the status report
$statusSection = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr') ? 'solr' : 'tika';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] =
    \ApacheSolrForTypo3\Tika\Report\TikaStatus::class;
$extIconPath = 'EXT:tika/Resources/Public/Images/Icons/';


$modulePrefix = 'extensions-tika-module';
$svgProvider = \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class;

/* @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */ // register all module icons with extensions-solr-module-modulename
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

    $iconRegistry->registerIcon(
        $modulePrefix . '-tikacontrolpanel',
        $svgProvider,
        ['source' => $extIconPath . 'module-tika.svg']
    );



if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr')) {
    $tikaExtensionConfiguration = \ApacheSolrForTypo3\Tika\Util::getTikaExtensionConfiguration();
    $isSolrModuleEnabled = (is_array($tikaExtensionConfiguration)
        && isset($tikaExtensionConfiguration['showTikaSolrModule'])
        && $tikaExtensionConfiguration['showTikaSolrModule'] == 1);

    if ($isSolrModuleEnabled) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'ApacheSolrForTypo3.tika',
            'searchbackend',
            'TikaControlPanel',
            'bottom',
            [
                'Backend\\SolrModule\\TikaControlPanelModule' => 'index, startServer, stopServer'
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:tika/Resources/Public/Images/Icons/module-tika.svg',
                'labels' => 'LLL:EXT:tika/Resources/Private/Language/locallang.xlf:solr.backend.tika.label'
            ]
        );
    }

}