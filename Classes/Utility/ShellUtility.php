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
class ShellUtility
{
    /**
     * @return string
     */
    public static function getLanguagePrefix()
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) && TYPO3_OS !== 'WIN') {
            return 'LC_CTYPE="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] . '" ';
        }
        return '';
    }

    /**
     * Backwards compatibility to 6.x, is available in CommandUtility in 7.x
     *
     * @param string $argument
     * @return string
     */
    public static function escapeShellArgument($argument)
    {
        $currentLocale = null;
        $isUTF8Filesystem = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']);
        if ($isUTF8Filesystem) {
            $currentLocale = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE,
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
        }

        $argument = escapeshellarg($argument);

        if ($isUTF8Filesystem) {
            setlocale(LC_CTYPE, $currentLocale);
        }

        return $argument;
    }
}
