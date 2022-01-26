<?php

declare(strict_types=1);
$_EXTKEY = 'tika';
$rootPath = __DIR__ . '/../../';
include($rootPath . 'ext_emconf.php');
$version = $EM_CONF['tika']['version'];
$validVersionPattern = '/^(\d+\.)?(\d+\.)?(\*|\d+)$/';
$match = preg_match($validVersionPattern, $version);

if ($match > 0) {
    echo 'Version was a valid release version: ' . $version . PHP_EOL;
    exit(0);
}
    echo 'Version was NOT a valid release version: ' . $version . PHP_EOL;
    exit(1);
