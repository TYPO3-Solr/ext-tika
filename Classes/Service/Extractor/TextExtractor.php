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

use ApacheSolrForTypo3\Tika\Service\File\SizeValidator;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Util;
use Exception;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * A service to extract text from files using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package ApacheSolrForTypo3\Tika\Service\Extractor
 */
class TextExtractor implements TextExtractorInterface
{

    /**
     * @var array
     */
    protected $configuration;

    /**
     * Supported file types (by extension)
     * @TODO query Tika for supported extensions
     *
     * @var array
     */
    protected $supportedFileTypes = [
        'doc',
        'docx',
        'epub',
        'htm',
        'html',
        'msg',
        'odf',
        'odt',
        'pdf',
        'ppt',
        'pptx',
        'rtf',
        'sxw',
        'txt',
        'xls',
        'xlsx',
        'zip'
    ];

    /**
     * @var SizeValidator
     */
    private $fileSizeValidator;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->configuration = Util::getTikaExtensionConfiguration();
        $this->fileSizeValidator = GeneralUtility::makeInstance(SizeValidator::class);
    }

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param FileInterface $file
     * @return bool
     */
    public function canExtractText(FileInterface $file)
    {
        $isSupportedFileExtension = in_array($file->getExtension(), $this->supportedFileTypes);
        $isSizeBelowLimit = $this->fileSizeValidator->isBelowLimit($file);

        return $isSizeBelowLimit && $isSupportedFileExtension;
    }

    /**
     * Extracts text from a file using Apache Tika
     *
     * @param FileInterface $file
     * @return string Text extracted from the input file
     * @throws Exception
     */
    public function extractText(FileInterface $file)
    {
        $extractedContent = null;

        $tika = ServiceFactory::getTika($this->configuration['extractor']);
        $extractedContent = $tika->extractText($file);

        return $extractedContent;
    }
}
