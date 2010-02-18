<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
		// adding the Solr connection status to the status report
	$statusSection = t3lib_extMgm::isLoaded('solr') ? 'solr' : 'tika';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] = 'tx_tika_report_TikaStatus';
}

?>