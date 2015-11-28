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

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * A Tika service implementation using the tika-server.jar
 *
 */
class ServerService extends AbstractService
{

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
    protected function initializeService()
    {
        $this->tikaUrl = 'http://'
            . $this->configuration['tikaServerHost'] . ':'
            . $this->configuration['tikaServerPort'];
    }

    /**
     * Initializes a Tika server process.
     *
     * @param string $arguments
     * @return \ApacheSolrForTypo3\Tika\Process
     */
    protected function getProcess($arguments = '')
    {
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
    protected function getStartCommand()
    {
        $tikaJar = GeneralUtility::getFileAbsFileName(
            $this->configuration['tikaServerPath'],
            false
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
    public function startServer()
    {
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
    public function stopServer()
    {
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
    public function getServerPid()
    {
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
    public function isServerRunning()
    {
        $pid = $this->getServerPid();

        return !empty($pid);
    }

    /**
     * Ping the Tika server
     *
     * @return bool true if the Tika server can be reached, false if not
     * @throws \Exception
     */
    public function ping()
    {
        $tikaPing = $this->queryTika('/tika');
        $tikaReachable = GeneralUtility::isFirstPartOfStr($tikaPing,
            'This is Tika Server.');

        return $tikaReachable;
    }

    /**
     * The tika server is available when the server is pingable.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->ping();
    }

    /**
     * Constructs the Tika server URL.
     *
     * @return string Tika server URL
     */
    public function getTikaServerUrl()
    {
        return $this->tikaUrl;
    }

    /**
     * Gets the Tika server version
     *
     * @return string Tika server version string
     * @throws \Exception
     */
    public function getTikaVersion()
    {
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
     * @param resource $context optional stream context
     * @return string Tika output
     * @throws \Exception
     */
    protected function queryTika($endpoint, $context = null)
    {
        $url = $this->getTikaServerUrl();
        $url .= $endpoint;

        $tikaOutput = '';
        try {
            $tikaOutput = file_get_contents($url, false, $context);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'Connection refused') === false &&
                strpos($message, 'HTTP request failed') === false
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
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file)
    {
        $headers = array(
            TYPO3_user_agent,
            'Accept: text/plain',
            'Content-Type: application/octet-stream',
            'Connection: close'
        );

        $context = stream_context_create(array(
            'http' => array(
                'protocol_version' => 1.1,
                'method' => 'PUT',
                'header' => implode(CRLF, $headers),
                'content' => $file->getContents()
            )
        ));

        $response = $this->queryTika('/tika', $context);

        return $response;
    }

    /**
     * Takes a file reference and extracts its meta data.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return array
     */
    public function extractMetaData(FileInterface $file)
    {
        $headers = array(
            TYPO3_user_agent,
            'Accept: application/json',
            'Content-Type: application/octet-stream',
            'Connection: close'
        );

        $context = stream_context_create(array(
            'http' => array(
                'protocol_version' => 1.1,
                'method' => 'PUT',
                'header' => implode(CRLF, $headers),
                'content' => $file->getContents()
            )
        ));

        $rawResponse = $this->queryTika('/meta', $context);
        $response = (array)json_decode($rawResponse);

        return $response;
    }

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file)
    {
        $headers = array(
            TYPO3_user_agent,
            'Content-Type: application/octet-stream',
            'Connection: close'
        );

        $context = stream_context_create(array(
            'http' => array(
                'protocol_version' => 1.1,
                'method' => 'PUT',
                'header' => implode(CRLF, $headers),
                'content' => $file->getContents()
            )
        ));

        $response = $this->queryTika('/language/stream', $context);

        return $response;
    }

    /**
     * Takes a string as input and detects its language.
     *
     * @param string $input
     * @return string Language ISO code
     */
    public function detectLanguageFromString($input)
    {
        $headers = array(
            TYPO3_user_agent,
            'Content-Type: application/octet-stream',
            'Connection: close'
        );

        $context = stream_context_create(array(
            'http' => array(
                'protocol_version' => 1.1,
                'method' => 'PUT',
                'header' => implode(CRLF, $headers),
                'content' => $input
            )
        ));

        $response = $this->queryTika('/language/string', $context);

        return $response;
    }

}
