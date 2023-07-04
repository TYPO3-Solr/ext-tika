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

use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use Psr\Http\Client\ClientExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;

/**
 * A service to detect a text's language using Apache Tika
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class LanguageDetector extends AbstractExtractor
{
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
    ];

    /**
     * @var int
     */
    protected int $priority = 98;

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param File $file
     * @return bool
     */
    public function canProcess(File $file): bool
    {
        $isSupportedFileType = in_array($file->getProperty('extension'), $this->supportedFileTypes);
        $isSizeBelowLimit = $this->fileSizeValidator->isBelowLimit($file);

        return $isSupportedFileType && $isSizeBelowLimit;
    }

    /**
     * Extracts meta data from a file using Apache Tika
     *
     * @param File $file
     * @param array $previousExtractedData Already extracted/existing data
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function extractMetaData(
        File $file,
        array $previousExtractedData = []
    ): array {
        $metaData = [];

        $tika = ServiceFactory::getTika($this->configuration['extractor']);
        $metaData['language'] = $tika->detectLanguageFromFile($file);

        return $metaData;
    }
}
