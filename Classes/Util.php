<?php
namespace ApacheSolrForTypo3\Tika;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2020 dkd Internet services GmbH <info@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Utility class for tx_tika
 */
class Util
{

    /**
     * @todo This method is just added for compatibility checks for TYPO3 version 9 and will be removed when TYPO9 support is dropped
     * @return boolean
     */
    public static function getIsTYPO3VersionBelow10()
    {
        return GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 10;
    }

    /**
     * @todo This method is just added for compatibility checks for TYPO3 version 9 and will be removed when TYPO9 support is dropped
     * @return boolean
     */
    public static function getIsTYPO3VersionAbove9()
    {
        return GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() > 9;
    }

    /**
     * @return boolean
     */
    public static function getIsTYPO3Version10Lts()
    {
        return GeneralUtility::makeInstance(Typo3Version::class)->getBranch() === '10.4';
    }
}
