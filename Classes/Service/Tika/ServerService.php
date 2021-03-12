<?php
namespace ApacheSolrForTypo3\Tika\Service\Tika;

/*
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

use ApacheSolrForTypo3\Tika\Process;
use ApacheSolrForTypo3\Tika\Util;
use ApacheSolrForTypo3\Tika\Utility\FileUtility;
use Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A Tika service implementation using the tika-server.jar
 *
 * @copyright (c) 2015 Ingo Renner <ingo@typo3.org>
 */
class ServerService extends AbstractService
{

    /**
     * @var ClientInterface
     */
    protected $psr7Client;

    /**
     * List of valid status codes
     *
     * @var int[]
     */
    protected $validStatusCodes = [200, 202];

    /**
     * Tika server URL
     *
     * @var Uri
     */
    protected $tikaUrl = null;

    /**
     * @var array
     */
    protected static $supportedMimeTypes = [];

    /**
     * Service initialization
     *
     * @return void
     * @noinspection PhpUnused
     */
    protected function initializeService()
    {
        $this->psr7Client = GeneralUtility::getContainer()->get(ClientInterface::class);

        // Fallback default configuration is with http protocol
        $this->tikaUrl = new Uri('http://' . $this->configuration['tikaServerHost']);

        // Overwrite configuration of tikaServerScheme is configured
        if (!empty($this->configuration['tikaServerScheme'])) {
            $this->tikaUrl = $this->tikaUrl->withScheme($this->configuration['tikaServerScheme']);
        }

        // Only append tikaServerPort if configured
        if (!empty($this->configuration['tikaServerPort'])) {
            $this->tikaUrl = $this->tikaUrl->withPort((int)$this->configuration['tikaServerPort']);
        }
    }

    /**
     * Initializes a Tika server process.
     *
     * @param string $arguments
     * @return Process
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function getProcess($arguments = ''): Process
    {
        return GeneralUtility::makeInstance(Process::class, CommandUtility::getCommand('java'), $arguments);
    }

    /**
     * Creates the command to start the Tika server.
     *
     * @return string
     */
    protected function getStartCommand(): string
    {
        $tikaJar = FileUtility::getAbsoluteFilePath($this->configuration['tikaServerPath']);
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

        /* @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
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
        /* @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->remove('tx_tika', 'server.pid');
    }

    /**
     * Gets the Tika server pid.
     *
     * Tries to retrieve the pid from the TYPO3 registry first, then using ps.
     *
     * @return int|null Null if the pid can't be found, otherwise the pid
     */
    public function getServerPid(): ?int
    {
        /* @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $pid = $registry->get('tx_tika', 'server.pid');

        if (empty($pid)) {
            $process = $this->getProcess($this->getStartCommand());
            $pid = $process->findPid();
        }

        if (empty($pid)) {
            return null;
        }

        return (int)$pid;
    }

    /**
     * Check if the Tika server is running
     *
     * @return bool
     */
    public function isServerRunning(): bool
    {
        $pid = $this->getServerPid();

        return !empty($pid);
    }

    /**
     * Ping the Tika server
     *
     * @return bool true if the Tika server can be reached, false if not
     * @throws Exception
     */
    public function ping(): bool
    {
        try {
            $tikaPing = $this->queryTika($this->createRequestForEndpoint('/tika'));
            return GeneralUtility::isFirstPartOfStr($tikaPing, 'This is Tika Server');
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * The tika server is available when the server is pingable.
     *
     * @return bool
     * @throws Exception
     */
    public function isAvailable(): bool
    {
        return $this->ping();
    }

    /**
     * Constructs the Tika server URL.
     *
     * @return string Tika server URL
     */
    public function getTikaServerUrl(): string
    {
        return (string)$this->tikaUrl;
    }

    /**
     * Constructs the Tika server Uri.
     *
     * @return Uri Tika server Uri
     */
    public function getTikaServerUri(): Uri
    {
        return $this->tikaUrl;
    }

    /**
     * Gets the Tika server version
     *
     * @return string Tika server version string
     * @throws Exception
     */
    public function getTikaVersion(): string
    {
        $version = 'unknown';

        if ($this->isServerRunning()) {
            $version = $this->queryTika($this->createRequestForEndpoint('/version'));
        }

        return $version;
    }

    /**
     * Query a Tika server endpoint
     *
     * @param RequestInterface $request
     * @return string Tika output
     * @throws Exception
     */
    protected function queryTika(RequestInterface $request): string
    {
        $tikaOutput = '';
        try {
            $response = $this->psr7Client->sendRequest($request);
            if (!in_array($response->getStatusCode(), $this->validStatusCodes)) {
                throw new \Exception($response->getReasonPhrase(), $response->getStatusCode());
            }

            $tikaOutput = $response->getBody()->getContents();
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            print 'EXCEPTION: ' . $exception->getMessage() . PHP_EOL;
            if (
                strpos($message, 'Connection refused') === false &&
                strpos($message, 'HTTP request failed') === false
            ) {
                // If the server is simply not available it would say Connection refused
                // since that is not the case something else went wrong
                throw $exception;
            }
        }

        return $tikaOutput;
    }

    /**
     * Takes a file reference and extracts the text from it.
     *
     * @param FileInterface $file
     * @return string
     * @throws Exception
     */
    public function extractText(FileInterface $file): string
    {
        $request = $this->createRequestForEndpoint('/tika', 'PUT')
            ->withAddedHeader('Content-Type', 'application/octet-stream')
            ->withAddedHeader('Accept', 'text/plain')
            ->withAddedHeader('Connection', 'close')
            ->withProtocolVersion(1.1)
            ->withBody($this->convertFileIntoStream($file));

        $response = $this->queryTika($request);

        if ($response === false) {
            $this->log('Text Extraction using Tika Server failed', $this->getLogData($file, $response), 2);
        } else {
            $this->log('Text Extraction using Tika Server', $this->getLogData($file, $response));
        }

        return $response;
    }

    /**
     * Takes a file reference and extracts its meta data.
     *
     * @param FileInterface $file
     * @return array|null
     * @throws Exception
     */
    public function extractMetaData(FileInterface $file): ?array
    {
        $request = $this->createRequestForEndpoint('/meta', 'PUT')
            ->withAddedHeader('Content-Type', 'application/octet-stream')
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('Connection', 'close')
            ->withProtocolVersion(1.1)
            ->withBody($this->convertFileIntoStream($file));

        $rawResponse = $this->queryTika($request);
        $response = json_decode($rawResponse, true);

        if (!is_array($response)) {
            $this->log('Meta Data Extraction using Tika Server failed', $this->getLogData($file, $rawResponse), 2);
            return [];
        }

        $this->log('Meta Data Extraction using Tika Server', $this->getLogData($file, $rawResponse));
        return $response;
    }

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param FileInterface $file
     * @return string Language ISO code
     * @throws Exception
     */
    public function detectLanguageFromFile(FileInterface $file): string
    {
        $request = $this->createRequestForEndpoint('/language/stream', 'PUT')
            ->withAddedHeader('Content-Type', 'application/octet-stream')
            ->withAddedHeader('Connection', 'close')
            ->withProtocolVersion(1.1)
            ->withBody($this->convertFileIntoStream($file));

        $response = $this->queryTika($request);

        if ($response === false) {
            $this->log('Language Detection using Tika Server failed', $this->getLogData($file, $response), 2);
        } else {
            $this->log('Language Detection using Tika Server', $this->getLogData($file, $response));
        }

        return $response;
    }

    /**
     * Takes a string as input and detects its language.
     *
     * @param string $input
     * @return string Language ISO code
     * @throws Exception
     */
    public function detectLanguageFromString($input): string
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($input);
        $request = $this->createRequestForEndpoint('/language/string', 'PUT')
            ->withAddedHeader('Content-Type', 'application/octet-stream')
            ->withAddedHeader('Connection', 'close')
            ->withProtocolVersion(1.1)
            ->withBody($stream);

        return $this->queryTika($request);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSupportedMimeTypes(): array
    {
        if (is_array(self::$supportedMimeTypes) && count(self::$supportedMimeTypes) > 0) {
            return self::$supportedMimeTypes;
        }

        self::$supportedMimeTypes = $this->buildSupportedMimeTypes();

        return self::$supportedMimeTypes;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getMimeTypeJsonFromTikaServer(): string
    {
        $request = $this->createRequestForEndpoint('/mime-types', 'GET')
            ->withAddedHeader('Content-Type', 'application/octet-stream')
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('Connection', 'close')
            ->withProtocolVersion(1.1);

        return $this->queryTika($request);
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function buildSupportedMimeTypes(): array
    {
        $response = $this->getMimeTypeJsonFromTikaServer();

        $result = (json_decode($response));
        $definitions = get_object_vars($result);
        $coreTypes = [];
        $aliasTypes = [];
        foreach ($definitions as $coreMimeType => $configuration) {
            if (isset($configuration->alias) && is_array($configuration->alias)) {
                $aliasTypes += $configuration->alias;
            }
            $coreTypes[] = $coreMimeType;
        }

        $supportedTypes = $coreTypes + $aliasTypes;
        $supportedTypes = array_filter($supportedTypes);
        asort($supportedTypes);
        return $supportedTypes;
    }

    /**
     * Creates a new request with given method and given endpoint
     * This method is a wrapper for createRequest()
     *
     * @param string $endpoint
     * @param string $method
     * @return RequestInterface
     */
    protected function createRequestForEndpoint(string $endpoint, string $method = 'GET'): RequestInterface
    {
        return $this->createRequest($this->createEndpoint($endpoint), $method);
    }

    /**
     * Creates a new request with given method and uri
     *
     * @param UriInterface $uri
     * @param string $method
     * @return RequestInterface
     */
    protected function createRequest(UriInterface $uri, string $method = 'GET'): RequestInterface
    {
        /* @var RequestFactory $requestFactory*/
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $request = $requestFactory->createRequest(
            $method,
            $uri
        );
        return $request->withAddedHeader('User-Agent', $this->getUserAgent());
    }

    /**
     * Creates a new URI with given endpoint
     *
     * @param string $endpoint
     * @return Uri
     */
    protected function createEndpoint(string $endpoint): Uri
    {
        return $this->getTikaServerUri()
            ->withPath($endpoint);
    }

    /**
     * Convert a file into a stream
     *
     * @param FileInterface $file
     * @return Stream
     */
    protected function convertFileIntoStream(FileInterface $file): Stream
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($file->getContents());
        return $stream;
    }

    /**
     * @return string
     */
    protected function getUserAgent(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['HTTP']['headers']['User-Agent'] ?? 'TYPO3';
    }

    /**
     * @param FileInterface $file
     * @param string $response
     * @return array
     */
    protected function getLogData(FileInterface $file, string $response): array
    {
        return [
            'file' => $file->getName(),
            'file_path' => $file->getPublicUrl(),
            'tika_url' => $this->getTikaServerUrl(),
            'response' => $response
        ];
    }
}
