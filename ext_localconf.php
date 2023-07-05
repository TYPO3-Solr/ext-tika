<?php

declare(strict_types=1);

use ApacheSolrForTypo3\Tika\ContextMenu\Preview;
use ApacheSolrForTypo3\Tika\Hooks\BackendControllerHook;
use ApacheSolrForTypo3\Tika\Service\Extractor\LanguageDetector;
use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use ApacheSolrForTypo3\Tika\Service\Extractor\TextExtractor;
use ApacheSolrForTypo3\Tika\Util;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'] = [];
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'] = array_merge(
    [
        'Local',
    ],
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions']
);

/** @var ExtractorRegistry $metaDataExtractorRegistry */
$metaDataExtractorRegistry = GeneralUtility::makeInstance(ExtractorRegistry::class);
$metaDataExtractorRegistry->registerExtractionService(MetaDataExtractor::class);

$extConf = Util::getTikaExtensionConfiguration();
if ($extConf['extractor'] !== 'solr') {
    $metaDataExtractorRegistry->registerExtractionService(LanguageDetector::class);
}
unset($extConf);

/** @var TextExtractorRegistry $textExtractorRegistry */
$textExtractorRegistry = GeneralUtility::makeInstance(TextExtractorRegistry::class);
$textExtractorRegistry->registerTextExtractor(TextExtractor::class);

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1505197586] = Preview::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = BackendControllerHook::class . '->addJavaScript';
