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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * A Tika service implementation using the tika-server.jar
 *
 */
class ServerService extends AbstractTikaService {

	/**
	 * Tika server URL
	 *
	 * @var string
	 */
	protected $tikaUrl;


	/**
	 * Service initialization
	 *
	 * @return void
	 */
	protected function initializeService() {
		$this->tikaUrl = 'http://'
			. $this->configuration['tikaServerHost'] . ':'
			. $this->configuration['tikaServerPort'];
	}

	/**
	 * Initializes a process.
	 *
	 * @param string $arguments
	 * @return \ApacheSolrForTypo3\Tika\Process
	 */
	public function getProcess($arguments = '') {
		$process = GeneralUtility::makeInstance(
			'ApacheSolrForTypo3\\Tika\\Process',
			CommandUtility::getCommand('java'),
			$arguments
		);

		return $process;
	}

	/**
	 * Creates the command to start the Tika server.
	 *
	 * @return string
	 */
	protected function getStartCommand() {
		$tikaJar = GeneralUtility::getFileAbsFileName(
			$this->configuration['tikaServerPath'],
			FALSE
		);
		$command = '-jar ' . escapeshellarg($tikaJar);
		$command .= ' -p ' . escapeshellarg($this->configuration['tikaServerPort']);
		$command = escapeshellcmd($command);

		return $command;
	}

	/**
	 * Starts the Tika server
	 *
	 * @return void
	 */
	public function startServer() {
		$process = $this->getProcess($this->getStartCommand());
		$process->start();
		$pid = $process->getPid();

		$registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$registry->set('tx_tika', 'server.pid', $pid);
	}

	/**
	 * Stops the Tika server
	 *
	 * @return void
	 */
	public function stopServer() {
		$pid = $this->getServerPid();

		$process = $this->getProcess();
		$process->setPid($pid);
		$process->stop();

		// unset pid in registry
		$registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$registry->remove('tx_tika', 'server.pid');
	}

	/**
	 * Gets the Tika server pid.
	 *
	 * Tries to retrieve the pid from the TYPO3 registry first, then using ps.
	 *
	 * @return int|null Null if the pid can't be found, otherwise the pid
	 */
	public function getServerPid() {
		$registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$pid = $registry->get('tx_tika', 'server.pid');

		if (empty($pid)) {
			$process = $this->getProcess($this->getStartCommand());
			$pid = $process->findPid();
		}

		return $pid;
	}

	/**
	 * Check if the Tika server is running
	 *
	 * @return bool
	 */
	public function isServerRunning() {
		$pid = $this->getServerPid();

		return !empty($pid);
	}

	/**
	 * Ping the Tika server
	 *
	 * @return bool true if the Tika server can be reached, false if not
	 * @throws \Exception
	 */
	public function ping() {
		$tikaPing      = $this->queryTika('/tika');
		$tikaReachable = GeneralUtility::isFirstPartOfStr($tikaPing, 'This is Tika Server.');

		return $tikaReachable;
	}

	/**
	 * Constructs the Tika server URL.
	 *
	 * @return string Tika server URL
	 */
	public function getTikaServerUrl() {
		return $this->tikaUrl;
	}

	/**
	 * Gets the Tika server version
	 *
	 * @return string Tika server version string
	 * @throws \Exception
	 */
	public function getTikaVersion() {
		$version = 'unknown';

		if ($this->isServerRunning()) {
			$version = $this->queryTika('/version');
		}

		return $version;
	}

	/**
	 * Query a Tika server endpoint
	 *
	 * @param string $endpoint
	 * @return string Tika output
	 * @throws \Exception
	 */
	protected function queryTika($endpoint) {
		$url = $this->getTikaServerUrl();
		$url .= $endpoint;

		$tikaOutput = '';
		try {
			$tikaOutput = file_get_contents($url);
		} catch (\Exception $e) {
			$message = $e->getMessage();
			if (strpos($message, 'Connection refused') === FALSE &&
				strpos($message, 'HTTP request failed') === FALSE
			) {
				// If the server is simply not available it would say Connection refused
				// since that is not the case something else went wrong
				throw $e;
			}
		}

		return $tikaOutput;
	}

	/**
	 * Takes a file reference and extracts the text from it.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string
	 */
	public function extractText(File $file) {
		// TODO: Implement extractText() method.
	}

	/**
	 * Takes a file reference and extracts its meta data.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return array
	 */
	public function extractMetaData(File $file) {
		// TODO: Implement extractMetaData() method.
	}

	/**
	 * Takes a file reference and detects its content's language.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string Language ISO code
	 */
	public function detectLanguageFromFile(File $file) {
		// TODO: Implement detectLanguageFromFile() method.
	}

	/**
	 * Takes a string as input and detects its language.
	 *
	 * @param string $input
	 * @return string Language ISO code
	 */
	public function detectLanguageFromString($input) {
		// TODO: Implement detectLanguageFromString() method.
	}
}