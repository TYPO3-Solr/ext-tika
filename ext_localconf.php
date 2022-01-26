<?php

declare(strict_types=1);
// Prevent Script from beeing called directly
defined('TYPO3_MODE') || die();

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'] = [];
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'] = array_merge(
    [
        'Local',
    ],
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions']
);

$metaDataExtractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
$metaDataExtractorRegistry->registerExtractionService(\ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor::class);

$extConf = \ApacheSolrForTypo3\Tika\Util::getTikaExtensionConfiguration();
if ($extConf['extractor'] !== 'solr') {
    $metaDataExtractorRegistry->registerExtractionService(\ApacheSolrForTypo3\Tika\Service\Extractor\LanguageDetector::class);
}
unset($extConf);

$textExtractorRegistry = \TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry::getInstance();
$textExtractorRegistry->registerTextExtractor(\ApacheSolrForTypo3\Tika\Service\Extractor\TextExtractor::class);

// Add Context Menu and JS
if (TYPO3_MODE == 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1505197586] = \ApacheSolrForTypo3\Tika\ContextMenu\Preview::class;
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \ApacheSolrForTypo3\Tika\Hooks\BackendControllerHook::class . '->addJavaScript';
