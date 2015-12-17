<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
    // adding the Solr connection status to the status report
    $statusSection = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr') ? 'solr' : 'tika';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] = 'ApacheSolrForTypo3\\Tika\\Report\\TikaStatus';

    $iconPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY)
        . 'Resources/Public/Images/Icons/';
    \TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
        array(
            'ModuleTikaControlPanel' => $iconPath . 'Tika.png'
        ),
        $_EXTKEY
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
                array('index')
            );
        }
    }
}
