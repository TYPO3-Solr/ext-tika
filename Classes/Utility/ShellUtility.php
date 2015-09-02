<?php
namespace ApacheSolrForTypo3\Tika\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class ShellUtility
 */
class ShellUtility {
	/**
	 * @param string $file
	 * @return string
	 */
	public static function getLanguagePrefix($file) {
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			if (mb_detect_encoding($file, 'ASCII,UTF-8', true) == 'UTF-8') {
				return 'LANG="'. $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] .'" ';
			}
		}
		return '';
	}

	/**
	 * @param string $argument
	 * @return string
	 */
	public static function escapeShellArgument($argument) {
		$currentLocale = NULL;
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			$currentLocale = setlocale(LC_CTYPE, 0);
			setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
		}

		$argument = escapeshellarg($argument);

		if (isset($currentLocale)) {
			setlocale(LC_CTYPE, $currentLocale);
		}

		return $argument;
	}
}
