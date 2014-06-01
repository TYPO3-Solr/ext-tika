<?php
$extensionPath = t3lib_extMgm::extPath('tika');
return array(
	'tx_tika_statuscheck'               => $extensionPath . 'classes/class.tx_tika_statuscheck.php',

	// services

	'apachesolrfortypo3\tika\service\languagedetectionservice'  => $extensionPath . 'classes/service/languagedetectionservice.php',
	'apachesolrfortypo3\tika\service\metadataextractionservice' => $extensionPath . 'classes/service/metadataextractionservice.php',
	'apachesolrfortypo3\tika\service\textextractionservice'     => $extensionPath . 'classes/service/textextractionservice.php',

	'apachesolrfortypo3\tika\report\tikastatus' => $extensionPath . 'Classes/Report/TikaStatus.php',

	// interfaces

	'tx_tika_languagedetectionpostprocessor'  => $extensionPath . 'interfaces/interface.tx_tika_languagedetectionpostprocessor.php',
	'tx_tika_metadataextractionpostprocessor' => $extensionPath . 'interfaces/interface.tx_tika_metadataextractionpostprocessor.php',
	'tx_tika_textextractionpostprocessor'     => $extensionPath . 'interfaces/interface.tx_tika_textextractionpostprocessor.php'

);
?>
