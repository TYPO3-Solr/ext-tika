<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$extractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\MetaData');
$extractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\Language');
