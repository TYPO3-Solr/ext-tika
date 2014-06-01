<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tika');
return array(
	'tx_tika_statuscheck'               => $extensionPath . 'classes/class.tx_tika_statuscheck.php',

	// services

	'apachesolrfortypo3\tika\service\languagedetectionservice'  => $extensionPath . 'classes/service/languagedetectionservice.php',
	'apachesolrfortypo3\tika\service\metadataextractionservice' => $extensionPath . 'classes/service/metadataextractionservice.php',
	'apachesolrfortypo3\tika\service\textextractionservice'     => $extensionPath . 'classes/service/textextractionservice.php',

	'apachesolrfortypo3\tika\report\tikastatus' => $extensionPath . 'Classes/Report/TikaStatus.php'
);
?>
