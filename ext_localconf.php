<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$metaDataExtractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$metaDataExtractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\MetaDataExtractor');
$metaDataExtractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\LanguageDetector');

if (version_compare(TYPO3_version, '7.1', '>')) {
    $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
    $textExtractorRegistry->registerTextExtractor('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\TextExtractor');
}
