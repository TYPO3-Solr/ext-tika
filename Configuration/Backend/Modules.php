<?php

return [
    'tika' => [
        'parent' => 'searchbackend',
        'access' => 'user,group',
        'path' => '/module/searchbackend/tika',
        'icon' => 'EXT:tika/Resources/Public/Images/Icons/module-tika.svg',
        'labels' => 'LLL:EXT:tika/Resources/Private/Language/locallang.xlf:solr.backend.tika.label',
        'extensionName' => 'Tika',
        'controllerActions' => [
            \ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule\TikaControlPanelModuleController::class => [
                'index', 'startServer', 'stopServer',
            ],
        ],
    ],
];
