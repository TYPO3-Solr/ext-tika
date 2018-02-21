<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$metaDataExtractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$metaDataExtractorRegistry->registerExtractionService(\ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor::class);
$extConf = unserialize($_EXTCONF);
if ($extConf['extractor'] !== 'solr') {
    $metaDataExtractorRegistry->registerExtractionService(\ApacheSolrForTypo3\Tika\Service\Extractor\LanguageDetector::class);
}
unset($extConf);

if (version_compare(TYPO3_version, '7.1', '>')) {
    $textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
    $textExtractorRegistry->registerTextExtractor(\ApacheSolrForTypo3\Tika\Service\Extractor\TextExtractor::class);
}
// Add Context Menu and JS
if (TYPO3_MODE=='BE') {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1505197586] = \ApacheSolrForTypo3\Tika\ContextMenu\Preview::class;
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \ApacheSolrForTypo3\Tika\Hooks\BackendControllerHook::class . '->addJavaScript';