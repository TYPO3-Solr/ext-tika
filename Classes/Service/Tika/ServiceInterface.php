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

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * A common interface for the different ways of accessing Tika, e.g. app,
 * server, and Solr Cell.
 */
interface ServiceInterface
{
    /**
     * Gets the Tika version
     */
    public function getTikaVersion(): string;

    /**
     * Takes a file reference and extracts the text from it.
     */
    public function extractText(FileInterface $file): string;

    /**
     * Takes a file reference and extracts its meta-data.
     */
    public function extractMetaData(FileInterface $file): array;

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param FileInterface $file File to detect language from
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file): string;

    /**
     * Takes a string as input and detects its language.
     *
     * @param string $input String to detect language from
     * @return string Language ISO code
     */
    public function detectLanguageFromString(string $input): string;

    public function getSupportedMimeTypes(): array;

    /**
     * Public method to check the availability of this service.
     */
    public function isAvailable(): bool;
}
