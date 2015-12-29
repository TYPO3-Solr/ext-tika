<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Apache Tika for TYPO3',
    'description' => 'Provides Tika services for TYPO3 to detect a document\'s language, extract meta data, and extract content from files. Can either use a stand alone Tika executable or Tika integrated in a Solr server with an activated extracting request handler.',
    'version' => '2.0.1-dev',
    'state' => 'stable',
    'category' => 'services',
    'author' => 'Ingo Renner',
    'author_email' => 'ingo@typo3.org',
    'author_company' => 'dkd Internet Service GmbH',
    'module' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.0-7.99.99',
            'filemetadata' => '',
        ),
        'conflicts' => array(),
        'suggests' => array(
            'solr' => '3.1.0-',
            'devlog' => '',
        ),
    ),
    '_md5_values_when_last_written' => ''
);
