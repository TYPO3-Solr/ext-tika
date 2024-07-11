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

namespace ApacheSolrForTypo3\Tika\Service\Extractor;

use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A service to extract meta-data from files using Apache Tika
 */
class MetaDataExtractor extends AbstractExtractor
{
    protected int $priority = 100;

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws Throwable
     */
    public function canProcess(File $file): bool
    {
        $tikaService = $this->getExtractor();
        $mimeTypes = $tikaService->getSupportedMimeTypes();
        $allowedMimeTypes = $this->mergeAllowedMimeTypes($mimeTypes);

        $isAllowedMimetype = in_array($file->getMimeType(), $allowedMimeTypes);
        $isSizeBelowLimit = $this->fileSizeValidator->isBelowLimit($file);

        return $isAllowedMimetype && $isSizeBelowLimit;
    }

    /**
     * Method to return a filtered $mimeTypes list - excludes the ones defined in
     * $this->configuration['excludeMimeTypes']
     */
    protected function mergeAllowedMimeTypes(array $mimeTypes): array
    {
        if (empty($this->configuration['excludeMimeTypes'])) {
            return $mimeTypes;
        }

        $allowedMimeTypes = GeneralUtility::trimExplode(',', $this->configuration['excludeMimeTypes']);

        return array_diff($mimeTypes, $allowedMimeTypes);
    }

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    protected function getExtractor(): AppService|ServerService|SolrCellService
    {
        return ServiceFactory::getTika($this->configuration['extractor']);
    }

    /**
     * Extracts meta-data from a file using Apache Tika
     *
     * @param File $file File to extract meta-data from
     * @param array $previousExtractedData Already extracted/existing data
     *
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws Throwable
     */
    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        $extractedMetaData = $this->getExtractedMetaDataFromTikaService($file);
        return $this->normalizeMetaData($extractedMetaData);
    }

    /**
     * Creates an instance of the service and returns the result from "extractMetaData".
     *
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws Throwable
     */
    protected function getExtractedMetaDataFromTikaService(FileInterface $file): array
    {
        $tikaService = $this->getExtractor();
        return $tikaService->extractMetaData($file);
    }

    /**
     * Normalizes the names / keys of the meta-data found.
     *
     * @param array $metaData An array of raw meta-data from a file
     * @return array An array with cleaned meta-data keys
     */
    protected function normalizeMetaData(array $metaData): array
    {
        $metaDataCleaned = [];

        foreach ($metaData as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            if (empty($value)) {
                continue;
            }

            // clean / add values under alternative names
            switch ($key) {
                case 'dc:title':
                case 'title':
                    $metaDataCleaned['title'] = $value;
                    break;
                case 'dc:creator':
                case 'meta:author':
                case 'Author':
                case 'creator':
                    $metaDataCleaned['creator'] = $value;
                    break;
                case 'dc:publisher':
                    $metaDataCleaned['publisher'] = $value;
                    break;
                case 'height':
                    $metaDataCleaned['height'] = $value;
                    break;
                case 'Exif Image Height':
                    [$height] = explode(' ', $value, 2);
                    $metaDataCleaned['height'] = $height;
                    break;
                case 'width':
                    $metaDataCleaned['width'] = $value;
                    break;
                case 'Exif Image Width':
                    [$width] = explode(' ', $value, 2);
                    $metaDataCleaned['width'] = $width;
                    break;
                case 'Color space':
                    if ($value != 'Undefined') {
                        $metaDataCleaned['color_space'] = $value;
                    }
                    break;
                case 'Image Description':
                case 'Jpeg Comment':
                case 'subject':
                case 'dc:description':
                    $metaDataCleaned['description'] = $value;
                    break;
                case 'Headline':
                    $metaDataCleaned['alternative'] = $value;
                    break;
                case 'dc:subject':
                case 'meta:keyword':
                case 'Keywords':
                    $metaDataCleaned['keywords'] = $value;
                    break;
                case 'Copyright Notice':
                    $metaDataCleaned['note'] = $value;
                    break;
                case 'dcterms:created':
                case 'meta:creation-date':
                case 'Creation-Date':
                    $metaDataCleaned['content_creation_date'] = strtotime($value);
                    break;
                case 'Date/Time Original':
                    $metaDataCleaned['content_creation_date'] = $this->exifDateToTimestamp($value);
                    break;
                case 'dcterms:modified':
                case 'meta:save-date':
                case 'Last-Save-Date':
                case 'Last-Modified':
                    $metaDataCleaned['content_modification_date'] = strtotime($value);
                    break;
                case 'xmpTPg:NPages':
                case 'Page-Count':
                    $metaDataCleaned['pages'] = $value;
                    break;
                case 'Application-Name':
                case 'xmp:CreatorTool':
                    $metaDataCleaned['creator_tool'] = $value;
                    break;
                default:
                    // ignore
            }
        }

        return $metaDataCleaned;
    }

    /**
     * Converts a date string into timestamp
     * exif-tags: 2002:09:07 15:29:52
     *
     * @param string $date An exif date string
     * @return int Unix timestamp
     */
    protected function exifDateToTimestamp(string $date): int
    {
        if (($timestamp = strtotime($date)) === -1) {
            $date = 0;
        } else {
            $date = $timestamp;
        }

        return $date;
    }
}
