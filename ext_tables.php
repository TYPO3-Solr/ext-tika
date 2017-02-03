<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    // adding the Solr connection status to the status report
    $statusSection = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr') ? 'solr' : 'tika';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] = 'ApacheSolrForTypo3\\Tika\\Report\\TikaStatus';
    $extIconPath = 'EXT:tika/Resources/Public/Images/Icons/';


    $modulePrefix = 'extensions-tika-module';
    $bitmapProvider = 'TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider';

    // register all module icons with extensions-solr-module-modulename
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconRegistry');

    $iconRegistry->registerIcon(
        $modulePrefix . '-tikacontrolpanel',
        $bitmapProvider,
        ['source' => $extIconPath . 'Tika.png']
    );



    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr')) {
        $tikaExtensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
        $isSolrModuleEnabled = (is_array($tikaExtensionConfiguration)
            && isset($tikaExtensionConfiguration['showTikaSolrModule'])
            && $tikaExtensionConfiguration['showTikaSolrModule'] == 1);

        if ($isSolrModuleEnabled) {
            \ApacheSolrForTypo3\Solr\Backend\SolrModule\AdministrationModuleManager::registerModule(
                'ApacheSolrForTypo3.' . $_EXTKEY,
                'TikaControlPanel',
                ['index']
            );
        }
    }
}
