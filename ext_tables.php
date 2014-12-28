<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	// adding the Solr connection status to the status report
	$statusSection = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr') ? 'solr' : 'tika';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] = 'ApacheSolrForTypo3\\Tika\\Report\\TikaStatus';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'ApacheSolrForTypo3\\Tika\\StatusCheck->updateStatus';
// checking availability. Must do this here, DB connection is not available yet when ext_localconf.php is loaded
#$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_Registry');
#$servicesAvailable = $registry->get('Tx_Tika', 'available', FALSE);

// Permanently enabled for now
#TODO use registry with TYPO3 CMS 6.x, too. Must figure out earliest availability of DB
$servicesAvailable = true;

$GLOBALS['T3_SERVICES']['metaExtract']['Tx_Tika_MetaExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['textExtract']['Tx_Tika_TextExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['textLang']['Tx_Tika_TextLang']['available'] = $servicesAvailable;

$GLOBALS['T3_SERVICES']['Tx_Tika_MetaExtract']['Tx_Tika_MetaExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['Tx_Tika_TextExtract']['Tx_Tika_TextExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['Tx_Tika_TextLang']['Tx_Tika_TextLang']['available'] = $servicesAvailable;
