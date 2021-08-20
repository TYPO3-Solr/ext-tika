<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Apache Tika for TYPO3',
    'description' => 'Provides Tika services for TYPO3 to detect a document\'s language, extract meta data, and extract content from files. Can either use a stand alone Tika executable or Tika integrated in a Solr server with an activated extracting request handler.',
    'version' => '10.0.0',
    'state' => 'stable',
    'category' => 'services',
    'author' => 'Ingo Renner, Timo Hund, Markus Friedrich',
    'author_email' => 'ingo@typo3.org',
    'author_company' => 'dkd Internet Service GmbH',
    'module' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.10-10.4.99',
            'filemetadata' => ''
        ],
        'conflicts' => [],
        'suggests' => [
            'solr' => '11.1.0-0.0.0'
        ],
    ],
    '_md5_values_when_last_written' => ''
];
