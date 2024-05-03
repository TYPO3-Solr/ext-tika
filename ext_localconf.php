<?php

declare(strict_types=1);

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

$extConf = Util::getTikaExtensionConfiguration();
$registerMetaDataExtractorConf = $extConf['registerMetaDataExtractor'] ?? 1;
if ($registerMetaDataExtractorConf == 1) {
    /** @var ExtractorRegistry $metaDataExtractorRegistry */
    $metaDataExtractorRegistry = GeneralUtility::makeInstance(ExtractorRegistry::class);
    $metaDataExtractorRegistry->registerExtractionService(MetaDataExtractor::class);

    if ($extConf['extractor'] !== 'solr') {
        $metaDataExtractorRegistry->registerExtractionService(LanguageDetector::class);
    }
}

unset($extConf);

/** @var TextExtractorRegistry $textExtractorRegistry */
$textExtractorRegistry = GeneralUtility::makeInstance(TextExtractorRegistry::class);
$textExtractorRegistry->registerTextExtractor(TextExtractor::class);
