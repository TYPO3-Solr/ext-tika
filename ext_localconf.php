<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$metaDataExtractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$metaDataExtractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\MetaDataExtractor');
$metaDataExtractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\LanguageDetector');

$textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
$textExtractorRegistry->registerTextExtractor(\ApacheSolrForTypo3\Tika\Service\Extractor\TextExtractor::class);