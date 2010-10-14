<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
		// adding the Solr connection status to the status report
	$statusSection = t3lib_extMgm::isLoaded('solr') ? 'solr' : 'tika';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] = 'tx_tika_report_TikaStatus';
}

	// checking availability. Must do this here, DB connection is not available yet when ext_localconf.php is loaded
$registry = t3lib_div::makeInstance('t3lib_Registry');
$servicesAvailable = $registry->get('tx_tika', 'available', FALSE);

$GLOBALS['T3_SERVICES']['metaExtract']['tx_tika_metaExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['textExtract']['tx_tika_textExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['textLang']['tx_tika_textLang']['available'] = $servicesAvailable;

$GLOBALS['T3_SERVICES']['tx_tika_metaExtract']['tx_tika_metaExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['tx_tika_textExtract']['tx_tika_textExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['tx_tika_textLang']['tx_tika_textLang']['available'] = $servicesAvailable;

?>