<?php
namespace ApacheSolrForTypo3\Tika\Service\Tika;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Provides a Tika service depending on the extension's configuration
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class TikaServiceFactory {

	/**
	 * Creates an instance of a Tika service
	 *
	 * @param string $tikaService Tika Service type, one of jar, server, or solr (or tika for BC, same as jar)
	 * @return AppService|ServerService|SolrCellService
	 *
	 * @throws \InvalidArgumentException for unknown Tika service type
	 */
	public static function getTika($tikaService) {
		switch ($tikaService) {
			case 'jar':
			case 'tika': // backwards compatibility only
				return GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\Service\\Tika\\AppService');
			case 'server':
				return GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\Service\\Tika\\ServerService');
			case 'solr':
				return GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\Service\\Tika\\SolrCellService');
			default:
				throw new \InvalidArgumentException(
					'Unknown Tika service type "' . $tikaService . '". Must be one of jar, server, or solr.',
					1423035119
				);
		}
	}

}