<?php
namespace ApacheSolrForTypo3\Tika\Service\Extractor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Ingo Renner <ingo@typo3.org>
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

use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use TYPO3\CMS\Core\Resource\File;

/**
 * A service to extract meta data from files using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
class MetaDataExtractor extends AbstractExtractor
{

    protected $supportedFileTypes = array(
        'aiff',
        'au',
        'bmp',
        'doc',
        'docx',
        'epub',
        'flv',
        'gif',
        'htm',
        'html',
        'jpg',
        'jpeg',
        'mid',
        'mp3',
        'msg',
        'odf',
        'odt',
        'pdf',
        'png',
        'ppt',
        'pptx',
        'rtf',
        'svg',
        'sxw',
        'tgz',
        'tiff',
        'txt',
        'wav',
        'xls',
        'xlsx',
        'xml',
        'zip'
    );

    /**
     * @var integer
     */
    protected $priority = 100;


    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param File $file
     * @return boolean
     */
    public function canProcess(File $file)
    {
        // TODO use MIME type instead of extension
        // tika.jar --list-supported-types -> cache supported types
        // compare to file's MIME type

        return in_array($file->getProperty('extension'),
            $this->supportedFileTypes);
    }

    /**
     * Extracts meta data from a file using Apache Tika
     *
     * @param File $file
     * @param array $previousExtractedData Already extracted/existing data
     * @return array
     */
    public function extractMetaData(
        File $file,
        array $previousExtractedData = array()
    ) {
        $extractedMetaData = $this->getExtractedMetaDataFromTikaService($file);
        $metaData = $this->normalizeMetaData($extractedMetaData);

        return $metaData;
    }

    /**
     * Creates an instance of the service and returns the result from "extractMetaData".
     *
     * @param File $file
     * @return array
     */
    protected function getExtractedMetaDataFromTikaService($file)
    {
        $tikaService = ServiceFactory::getTika($this->configuration['extractor']);

        return $tikaService->extractMetaData($file);
    }

    /**
     * Normalizes the names / keys of the meta data found.
     *
     * @param array $metaData An array of raw meta data from a file
     * @return array An array with cleaned meta data keys
     */
    protected function normalizeMetaData(array $metaData)
    {
        $metaDataCleaned = array();

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
                case 'Image Height':
                    list($height) = explode(' ', $value, 2);
                    $metaDataCleaned['height'] = $height;
                    break;
                case 'width':
                    $metaDataCleaned['width'] = $value;
                    break;
                case 'Image Width':
                    list($width) = explode(' ', $value, 2);
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
     * exiftags: 2002:09:07 15:29:52
     *
     * @param string $date An exif date string
     * @return integer Unix timestamp
     */
    protected function exifDateToTimestamp($date)
    {
        if (is_string($date)) {
            if (($timestamp = strtotime($date)) === -1) {
                $date = 0;
            } else {
                $date = $timestamp;
            }
        }

        return $date;
    }
}
