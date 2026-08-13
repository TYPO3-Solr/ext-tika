<?php

declare(strict_types=1);

use ApacheSolrForTypo3\Tika\Service\Extractor\TextExtractor;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'] = [];
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'] = array_merge(
    [
        'Local',
    ],
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tika']['extractor']['driverRestrictions'],
);

/** @var TextExtractorRegistry $textExtractorRegistry */
$textExtractorRegistry = GeneralUtility::makeInstance(TextExtractorRegistry::class);
$textExtractorRegistry->registerTextExtractor(TextExtractor::class);
