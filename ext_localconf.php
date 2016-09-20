<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$metaDataExtractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$metaDataExtractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\MetaDataExtractor');
$extConf = unserialize($_EXTCONF);
if ($extConf['extractor'] !== 'solr') {
    $metaDataExtractorRegistry->registerExtractionService('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\LanguageDetector');
}
unset($extConf);

if (version_compare(TYPO3_version, '7.1', '>')) {
    $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
    $textExtractorRegistry->registerTextExtractor('ApacheSolrForTypo3\\Tika\\Service\\Extractor\\TextExtractor');
}
