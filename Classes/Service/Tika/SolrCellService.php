<?php

declare(strict_types=1);

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

namespace ApacheSolrForTypo3\Tika\Service\Tika;

use ApacheSolrForTypo3\Solr\ConnectionManager;
use ApacheSolrForTypo3\Solr\Exception\InvalidConnectionException;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\SolrConnection;
use ApacheSolrForTypo3\Tika\Util;
use Solarium\QueryType\Extract\Query;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A Tika service implementation using a Solr server
 */
class SolrCellService extends AbstractService
{
    /**
     * Solr connection
     */
    protected SolrConnection $solrConnection;

    /**
     * Service initialization
     *
     * @throws InvalidConnectionException
     */
    protected function initializeService(): void
    {
        // EM might define a different connection than already in use by
        // Index Queue
        /** @var ConnectionManager $connectionManager */
        $connectionManager =  GeneralUtility::makeInstance(ConnectionManager::class);

        $readEndpoint = [
            'scheme' => Util::convertEnvVarStringToValue((string)$this->configuration['solrScheme']),
            'host' => Util::convertEnvVarStringToValue((string)$this->configuration['solrHost']),
            'port' => (int)(Util::convertEnvVarStringToValue((string)$this->configuration['solrPort'])),
            'path' => Util::convertEnvVarStringToValue((string)$this->configuration['solrPath']),
            'core' => Util::convertEnvVarStringToValue((string)$this->configuration['solrCore']),
        ];
        if (!empty(trim($this->configuration['solrUsername'] ?? ''))
            && !empty(trim($this->configuration['solrPassword'] ?? ''))
        ) {
            $readEndpoint['username'] = Util::convertEnvVarStringToValue($this->configuration['solrUsername']);
            $readEndpoint['password'] = Util::convertEnvVarStringToValue($this->configuration['solrPassword']);
        }
        $writeEndpoint = $readEndpoint;
        $this->solrConnection = $connectionManager->getSolrConnectionForEndpoints(
            $readEndpoint,
            $writeEndpoint,
            new TypoScriptConfiguration([]),
        );
    }

    public function getSolrConnection(): SolrConnection
    {
        return $this->solrConnection;
    }

    /**
     * Retrieves a configuration value or a default value when not available.
     *
     * @noinspection PhpUnused
     */
    protected function getConfigurationOrDefaultValue(string $key, mixed $defaultValue): mixed
    {
        return $this->configuration[$key] ?? $defaultValue;
    }

    /**
     * Takes a file reference and extracts the text from it.
     */
    public function extractText(FileInterface $file): string
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        /** @var Query $query */
        $query = GeneralUtility::makeInstance(Query::class);
        $query->setFile($localTempFilePath);
        $query->setExtractOnly(true);
        $query->addParam('extractFormat', 'text');

        $writer = $this->solrConnection->getWriteService();
        $response = $writer->extractByQuery($query);

        $this->log('Text Extraction using Solr', [
            'file' => $file,
            'solr connection' => (array)$writer,
            'query' => (array)$query,
            'response' => $response,
        ]);

        return $response[0] ?? '';
    }

    /**
     * Takes a file reference and extracts its meta-data.
     */
    public function extractMetaData(FileInterface $file): array
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        /** @var Query $query */
        $query = GeneralUtility::makeInstance(Query::class);
        $query->setFile($localTempFilePath);
        $query->setExtractOnly(true);
        $query->addParam('extractFormat', 'text');

        $writer = $this->solrConnection->getWriteService();
        $response = $writer->extractByQuery($query);

        $metaData = [];
        if (isset($response[1]) && is_array($response[1])) {
            $metaData = $this->solrResponseToArray($response[1]);
        }

        $this->log('Meta Data Extraction using Solr', [
            'file' => $file,
            'solr connection' => (array)$writer,
            'query' => (array)$query,
            'response' => $response,
            'meta data' => $metaData,
        ]);

        return $metaData;
    }

    /**
     * Takes a file reference and detects its content's language.
     *
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file): string
    {
        // TODO check whether Solr supports text extraction now
        throw new UnsupportedOperationException(
            'The Tika Solr service does not support language detection',
            1423457153
        );
    }

    /**
     * Takes a string as input and detects its language.
     *
     * @return string Language ISO code
     */
    public function detectLanguageFromString(string $input): string
    {
        // TODO check whether Solr supports text extraction now
        throw new UnsupportedOperationException(
            'The Tika Solr service does not support language detection',
            1423457154
        );
    }

    /**
     * Turns the nested Solr response into the same format as produced by a
     * local Tika jar call
     *
     * @param array $metaDataResponse The part of the Solr response containing the meta-data
     * @return array The cleaned meta-data, matching the Tika jar call format
     */
    protected function solrResponseToArray(array $metaDataResponse = []): array
    {
        $cleanedData = [];

        foreach ($metaDataResponse as $dataName => $dataArray) {
            if (!($dataName % 2) == 0) {
                continue;
            }
            $fieldName = $dataArray;
            $fieldValue = $metaDataResponse[$dataName + 1] ?? [''];

            $cleanedData[$fieldName] = $fieldValue[0];
        }

        return $cleanedData;
    }

    /**
     * Gets the Tika version
     *
     * @return string Apache Solr server version string
     */
    public function getTikaVersion(): string
    {
        // TODO add patch for endpoint on Apache Solr to return Tika version
        // for now returns the Solr version string f.e. "Apache Solr X.Y.Z"
        return $this->solrConnection->getAdminService()->getSolrServerVersion();
    }

    /**
     * Since solr cell does not allow to query the supported mimetypes, we return a list of known supported mimetypes here.
     */
    public function getSupportedMimeTypes(): array
    {
        $mapping = [
            'application/epub+zip' => ['epub'],
            'application/gzip' => ['gz', 'tgz'],
            'application/msword' => ['doc'],
            'application/pdf' => ['pdf'],
            'application/rtf' => ['rtf'],
            'application/vnd.ms-excel' => ['xsl'],
            'application/vnd.ms-outlook' => ['msg'],
            'application/vnd.oasis.opendocument.formula' => ['odf'],
            'application/vnd.oasis.opendocument.text' => ['odt'],
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.sun.xml.writer' => ['sxw'],
            'application/zip' => ['zip'],
            'application/x-midi' => ['mid'],
            'application/xml' => ['xml'],
            'audio/aiff' => ['aif', 'aiff'],
            'audio/basic' => ['au'],
            'audio/midi' => ['mid'],
            'audio/mpeg3' => ['mp3'],
            'audio/mpeg' => ['mp3'],
            'audio/wav' => ['wav'],
            'audio/x-mpeg-3' => ['mp3'],
            'audio/x-wav' => ['wav'],
            'image/bmp' => ['bmp'],
            'image/gif' => ['gif'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/svg+xml' => ['svg'],
            'image/tiff' => ['tif', 'tiff'],
            'text/html' => ['html', 'htm'],
            'text/plain' => ['txt'],
            'text/xml' => ['xml'],
            'video/mpeg' => ['mp3'],
            'video/x-mpeg' => ['mp3'],
        ];

        return array_keys($mapping);
    }

    /**
     * The service is available when the solr server is reachable.
     */
    public function isAvailable(): bool
    {
        return $this->solrConnection->getWriteService()->ping();
    }

    public function ping(): bool
    {
        return $this->isAvailable();
    }

    public function getTikaServerUrl(): string
    {
        return (string)$this->solrConnection->getAdminService();
    }
}
