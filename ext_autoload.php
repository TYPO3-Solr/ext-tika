<?php
$extensionPath = t3lib_extMgm::extPath('tika');
return array(
	'tx_tika_statuscheck'               => $extensionPath . 'classes/class.tx_tika_statuscheck.php',

	// services

	'tx_tika_languagedetectionservice'  => $extensionPath . 'classes/class.tx_tika_languagedetectionservice.php',
	'tx_tika_metadataextractionservice' => $extensionPath . 'classes/class.tx_tika_metadataextractionservice.php',
	'tx_tika_textextractionservice'     => $extensionPath . 'classes/class.tx_tika_textextractionservice.php',

	'apachesolrfortypo3\tika\report\tikastatus' => $extensionPath . 'Classes/Report/TikaStatus.php',

	// interfaces

	'tx_tika_languagedetectionpostprocessor'  => $extensionPath . 'interfaces/interface.tx_tika_languagedetectionpostprocessor.php',
	'tx_tika_metadataextractionpostprocessor' => $extensionPath . 'interfaces/interface.tx_tika_metadataextractionpostprocessor.php',
	'tx_tika_textextractionpostprocessor'     => $extensionPath . 'interfaces/interface.tx_tika_textextractionpostprocessor.php'

);
?>
