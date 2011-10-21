<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$PATH_tika = t3lib_extMgm::extPath($_EXTKEY);

$tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
$requiredExecutable = '';
if ($tikaConfiguration['extractor'] == 'tika') {
	$requiredExecutable = 'java';
}

t3lib_extMgm::addService($_EXTKEY, 'metaExtract', 'tx_tika_metaExtract', array(
	'title'       => 'Tika meta data extraction',
	'description' => 'Uses Apache Tika to extract meta data.',

	'subtype'     => 'aiff,au,bmp,doc,docx,epub,flv,gif,htm,html,image:exif,jpg,jpeg,mid,mp3,msg,odf,odt,pdf,png,ppt,pptx,rtf,svg,sxw,tiff,txt,wav,xls,xlsx,xml',

	'available'   => FALSE,
	'priority'    => 50,
	'quality'     => 50,

	'os'          => '',
	'exec'        => $requiredExecutable,

	'classFile'   => $PATH_tika . 'classes/class.tx_tika_metadataextractionservice.php',
	'className'   => 'tx_tika_MetaDataExtractionService',
));


t3lib_extMgm::addService($_EXTKEY, 'textExtract', 'tx_tika_textExtract', array(
	'title'       => 'Tika text extraction',
	'description' => 'Uses Apache Tika to extract text from files.',

	'subtype'     => 'doc,docx,epub,htm,html,msg,odf,odt,pdf,ppt,pptx,rtf,sxw,txt,xls,xlsx,xml',

	'available'   => FALSE,
	'priority'    => 50,
	'quality'     => 50,

	'os'          => '',
	'exec'        => $requiredExecutable,

	'classFile'   => $PATH_tika . 'classes/class.tx_tika_textextractionservice.php',
	'className'   => 'tx_tika_TextExtractionService',
));


t3lib_extMgm::addService($_EXTKEY, 'textLang', 'tx_tika_textLang', array(
	'title'       => 'Tika language detection',
	'description' => 'Uses Apache Tika to detect a document\'s language.',

	'subtype'     => '',

	'available'   => FALSE,
	'priority'    => 50,
	'quality'     => 50,

	'os'          => '',
	'exec'        => $requiredExecutable,

	'classFile'   => $PATH_tika . 'classes/class.tx_tika_languagedetectionservice.php',
	'className'   => 'tx_tika_LanguageDetectionService',
));

?>