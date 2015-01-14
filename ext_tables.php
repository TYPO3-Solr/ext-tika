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
