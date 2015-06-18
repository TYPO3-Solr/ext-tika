<?php
namespace ApacheSolrForTypo3\Tika;

/***************************************************************
*  Copyright notice
*
*  (c) 2011-2015 Ingo Renner <ingo@typo3.org>
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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Updates the registry to add information whether tika is available or not.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class StatusCheck {

	/**
	 * EXT:tika configuration.
	 *
	 * @var array
	 */
	protected $tikaConfiguration = array();


	/**
	 * Constructor, reads the configuration of the extension
	 *
	 */
	public function __construct() {
		$this->tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
	}

	/**
	 * Updates the Tika availability status in the registry when clearing the
	 * configuration cache.
	 *
	 * @param array $parameters An array of commands from data handler.
	 * @param DataHandler $dataHandler Back reference to the data handler (not used)
	 */
	public function updateStatus(array $parameters, DataHandler $dataHandler) {
		$clearCacheCommand = $parameters['cacheCmd'];

		if ($clearCacheCommand == 'all' || $clearCacheCommand == 'temp_CACHED') {
			$status = $this->getStatus();

			$registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
			$registry->set('Tx_Tika', 'available', $status);
		}
	}

	/**
	 * Check the Status if the configuration of tika ist correct
	 *
	 * @return boolean TRUE if the extension is correct configured
	 */
	public function getStatus() {
		$isConfigured = (
			$this->hasCompleteLocalTikaConfiguration()
			||
			$this->hasCompleteRemoteSolrExtractingRequestHandlerConfiguration()
		);

		return $isConfigured;
	}

	/**
	 * Checks whether the extension is configured to use a local Tika
	 * application, and if so whether it's correctly configured.
	 *
	 * @return boolean TRUE if the extension is configured to use a local Tika app and if it's correctly configured, FALSE otherwise
	 */
	protected function hasCompleteLocalTikaConfiguration() {
		$localConfigurationComplete = FALSE;

		if ($this->tikaConfiguration['extractor'] == 'tika'
			&& is_file(GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))
			&& CommandUtility::checkCommand('java')) {

			$localConfigurationComplete = TRUE;
		}

		if ($this->tikaConfiguration['logging']) {
			$registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
			$registryStatus = $registry->get('Tx_Tika', 'available');

			GeneralUtility::devLog(
				'Has complete local Tika configuration: ' . ($localConfigurationComplete == TRUE ? 'yes' : 'no'),
				'tika',
				0,
				array(
					'configuration'      => $this->tikaConfiguration,
					'javaFound'          => CommandUtility::checkCommand('java'),
					'tikaPath'           => $this->tikaConfiguration['tikaPath'],
					'absoluteTikaPath'   => GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE),
					'absoluteTikaExists' => is_file(GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE)) == TRUE ? 'yes' : 'no',
					'registryStatus'     => $registryStatus,
				)
			);
		}

		return $localConfigurationComplete;
	}

	/**
	 * Checks whether the extension is configured to use a remote Solr server
	 * and its Extracting Request Handler. If that's the case we try to ping the
	 * Solr server, too.
	 *
	 * @return boolean TRUE if the extension is configured to use a remote Solr server and if it's correctly configured, FALSE otherwise
	 */
	protected function hasCompleteRemoteSolrExtractingRequestHandlerConfiguration() {
		$remoteConfigurationComplete = FALSE;

		if ($this->tikaConfiguration['extractor'] == 'solr') {

			try {
				/* @var $solr \Tx_Solr_SolrService */
				$solr = GeneralUtility::makeInstance('tx_solr_ConnectionManager')->getConnection(
					$this->tikaConfiguration['solrHost'],
					$this->tikaConfiguration['solrPort'],
					$this->tikaConfiguration['solrPath'],
					$this->tikaConfiguration['solrScheme']
				);

				$solr->ping();
				$plugins = $solr->getPluginsInformation();

				if (array_key_exists('/update/extract', $plugins->plugins->QUERYHANDLER)) {
					$remoteConfigurationComplete = TRUE;
				}
			} catch (\Exception $e) {
				$remoteConfigurationComplete = FALSE;
			}
		}

		return $remoteConfigurationComplete;
	}


}
