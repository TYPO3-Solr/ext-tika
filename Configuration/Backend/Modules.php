<?php

use ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule\TikaControlPanelModuleController;
use ApacheSolrForTypo3\Tika\Util;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$enabledModules = [];

if (ExtensionManagementUtility::isLoaded('solr')) {
    $tikaExtensionConfiguration = Util::getTikaExtensionConfiguration();
    $isSolrModuleEnabled = (isset($tikaExtensionConfiguration['showTikaSolrModule'])
        && $tikaExtensionConfiguration['showTikaSolrModule'] == 1);

    if ($isSolrModuleEnabled) {
        $enabledModules['tika'] = [
            'parent' => 'searchbackend',
            'access' => 'user,group',
            'path' => '/module/searchbackend/tika',
            'icon' => 'EXT:tika/Resources/Public/Images/Icons/module-tika.svg',
            'labels' => 'LLL:EXT:tika/Resources/Private/Language/locallang.xlf:solr.backend.tika.label',
            'extensionName' => 'Tika',
            'controllerActions' => [
                TikaControlPanelModuleController::class => [
                    'index', 'startServer', 'stopServer',
                ],
            ],
        ];
    }
}

return $enabledModules;
