<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Service\Extractor;

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

use ApacheSolrForTypo3\Tika\Service\File\SizeValidator;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Util;
use Psr\Http\Client\ClientExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A service to extract text from files using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class TextExtractor implements TextExtractorInterface
{

    /**
     * @var array
     */
    protected array $configuration;

    /**
     * Supported file types (by extension)
     * @TODO query Tika for supported extensions
     *
     * @var array
     */
    protected array $supportedFileTypes = [
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
        'zip',
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
    public function canExtractText(FileInterface $file): bool
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
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function extractText(FileInterface $file): string
    {
        $tika = ServiceFactory::getTika($this->configuration['extractor']);
        return $tika->extractText($file);
    }
}
