<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$extractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\MetaDataExtractor');
$extractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\LanguageDetector');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['textExtractors']['tika'] = 'ApacheSolrForTypo3\\Tika\\Service\\Extractor\\TextExtractor';