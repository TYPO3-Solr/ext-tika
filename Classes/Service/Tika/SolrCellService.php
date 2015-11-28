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

use ApacheSolrForTypo3\Solr\SolrService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * A Tika service implementation using a Solr server
 *
 */
class SolrCellService extends AbstractService
{

    /**
     * Solr connection
     *
     * @var SolrService
     */
    protected $solr = null;

    /**
     * Service initialization
     *
     * @return void
     */
    protected function initializeService()
    {
        // FIXME move connection building to EXT:solr
        // currently explicitly using "new" to bypass
        // \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance() or providing a Factory

        // TODO just get *any* connection from EXT:solr

        // EM might define a different connection than already in use by
        // Index Queue
        $this->solr = new SolrService(
            $this->configuration['solrHost'],
            $this->configuration['solrPort'],
            $this->configuration['solrPath'],
            $this->configuration['solrScheme']
        );
    }

    /**
     * Takes a file reference and extracts the text from it.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $query = GeneralUtility::makeInstance(
            'ApacheSolrForTypo3\\Tika\\Service\\Tika\\SolrCellQuery',
            $localTempFilePath
        );
        $query->setExtractOnly();
        $response = $this->solr->extract($query);

        $this->cleanupTempFile($localTempFilePath, $file);

        $this->log('Text Extraction using Solr', array(
            'file' => $file,
            'solr connection' => (array)$this->solr,
            'query' => (array)$query,
            'response' => $response
        ));

        return $response[0];
    }

    /**
     * Takes a file reference and extracts its meta data.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return array
     */
    public function extractMetaData(FileInterface $file)
    {
        $localTempFilePath = $file->getForLocalProcessing(false);
        $query = GeneralUtility::makeInstance(
            'ApacheSolrForTypo3\\Tika\\Service\\Tika\\SolrCellQuery',
            $localTempFilePath
        );
        $query->setExtractOnly();
        $response = $this->solr->extract($query);
        $metaData = $this->solrResponseToArray($response[1]);

        $this->cleanupTempFile($localTempFilePath, $file);

        $this->log('Meta Data Extraction using Solr', array(
            'file' => $file,
            'solr connection' => (array)$this->solr,
            'query' => (array)$query,
            'response' => $response,
            'meta data' => $metaData
        ));

        return $metaData;
    }

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file)
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
     * @param string $input
     * @return string Language ISO code
     */
    public function detectLanguageFromString($input)
    {
        // TODO check whether Solr supports text extraction now
        throw new UnsupportedOperationException(
            'The Tika Solr service does not support language detection',
            1423457153
        );
    }

    /**
     * Turns the nested Solr response into the same format as produced by a
     * local Tika jar call
     *
     * @param array $metaDataResponse The part of the Solr response containing the meta data
     * @return array The cleaned meta data, matching the Tika jar call format
     */
    protected function solrResponseToArray(array $metaDataResponse)
    {
        $cleanedData = array();

        foreach ($metaDataResponse as $dataName => $dataArray) {
            $cleanedData[$dataName] = $dataArray[0];
        }

        return $cleanedData;
    }

    /**
     * Gets the Tika version
     *
     * @return string Apache Solr server version string
     */
    public function getTikaVersion()
    {
        // TODO add patch for endpoint on Apache Solr to return Tika version
        // for now returns the Solr version string f.e. "Apache Solr 5.2.0"
        return $this->solr->getSolrServerVersion();
    }

    /**
     * The service is available when the solr server is pingable.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->solr->ping();
    }

    /**
     * The endpoint of the solr server it scheme://host:port/path
     *
     * @return string
     */
    public function getEndpointIdentifier()
    {
        $endpoint = $this->getConfigurationOrDefaultValue('solrScheme','') . '://' .
                    $this->getConfigurationOrDefaultValue('solrHost','') . ':' .
                    $this->getConfigurationOrDefaultValue('solrPort','') .
                    $this->getConfigurationOrDefaultValue('solrPath','');

        return $endpoint;
    }
}
