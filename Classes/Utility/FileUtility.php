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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileUtility
 */
class FileUtility
{
    /**
     * @param string $path
     * @return string
     */
    public static function getAbsoluteFilePath($path) {
        if (version_compare(TYPO3_version, '8.0', '<')) {
            return GeneralUtility::getFileAbsFileName($path);
        } else {
            if (substr($path, 0, 1) === "/") {
                // if the path start with a "/" we thread it as absolute
                return $path;
            } else {
                return GeneralUtility::getFileAbsFileName($path);
            }
        }
    }
}
