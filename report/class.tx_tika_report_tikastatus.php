<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Ingo Renner <ingo@typo3.org>
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


/**
 * Provides an status report about whether Tika is properly configured
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class tx_tika_report_TikaStatus implements tx_reports_StatusProvider {

	/**
	 * Tika extension configuration.
	 *
	 * @var	array
	 */
	protected $tikaConfiguration = array();

	/**
	 * Constructor, reads the extension configuration.
	 */
	public function __construct() {
		$this->tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
	}

	/**
	 * Checks whether Tika is properly configured
	 *
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$reports = array();

		/* @var $status tx_reports_reports_status_Status */
		$status = t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Apache Tika',
			'Configuration OK'
		);

		if (!$this->isConfigured()) {
			$status = t3lib_div::makeInstance('tx_reports_reports_status_Status',
				'Apache Tika',
				'Configuration Incomplete',
				'<p>Please check your configuration for Apache Tika.</p><p>
				Either use a local Tika jar binary app and make sure Java is
				available or use a remote Solr server\'s Extracting Request
				Handler.</p>',
				tx_reports_reports_status_Status::ERROR
			);
		}

		$reports[] = $status;

		return $reports;
	}

	/**
	 * Checks whether the extension is properly configured, either using a local
	 * Tika binary or a remote Solr extraction request handler which internally
	 * uses Tika.
	 *
	 * @return	boolean	TRUE if a working configuration was detected, FALSE otherwise.
	 */
	protected function isConfigured() {
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
	 * @return	boolean	TRUE if the extension is configured to use a local Tika app and if it's correctly configured, FALSE otherwise
	 */
	protected function hasCompleteLocalTikaConfiguration() {
		$localConfigurationComplete = FALSE;

		if ($this->tikaConfiguration['extractor'] == 'tika'
			&& is_file($this->tikaConfiguration['tikaPath'])
			&& t3lib_exec::checkCommand('java')) {

			$localConfigurationComplete = TRUE;
		}

		return $localConfigurationComplete;
	}

	/**
	 * Checks whether the extension is configured to use a remote Solr server
	 * and its Extracting Request Handler. If that's the case we try to ping the
	 * Solr server, too.
	 *
	 * @return	boolean	TRUE if the extension is configured to use a remote Solr server and if it's correctly configured, FALSE otherwise
	 */
	protected function hasCompleteRemoteSolrExtractingRequestHandlerConfiguration() {
		$remoteConfigurationComplete = FALSE;

		if ($this->tikaConfiguration['extractor'] == 'solr') {

			try {
				/* @var $solr tx_solr_SolrService */
				$solr = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnection(
					$this->tikaConfiguration['solrHost'],
					$this->tikaConfiguration['solrPort'],
					$this->tikaConfiguration['solrPath']
				);

				$solr->ping();
				$plugins = $solr->getPluginsInformation();

				if (array_key_exists('/update/extract', $plugins->plugins->QUERYHANDLER)) {
					$remoteConfigurationComplete = TRUE;
				}
			} catch (Exception $e) {
				$remoteConfigurationComplete = FALSE;
			}
		}

		return $remoteConfigurationComplete;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tika/report/class.tx_tika_report_tikastatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tika/report/class.tx_tika_report_tikastatus.php']);
}

?>