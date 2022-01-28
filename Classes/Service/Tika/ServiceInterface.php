<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * A common interface for the different ways of accessing Tika, e.g. app,
 * server, and Solr Cell.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
interface ServiceInterface
{
    /**
     * Gets the Tika version
     *
     * @return string
     */
    public function getTikaVersion(): string;

    /**
     * Takes a file reference and extracts the text from it.
     *
     * @param FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file): string;

    /**
     * Takes a file reference and extracts its meta-data.
     *
     * @param FileInterface $file
     * @return array
     */
    public function extractMetaData(FileInterface $file): array;

    /**
     * Takes a file reference and detects its content's language.
     *
     * @param FileInterface $file
     * @return string Language ISO code
     */
    public function detectLanguageFromFile(FileInterface $file): string;

    /**
     * Takes a string as input and detects its language.
     *
     * @param string $input
     * @return string Language ISO code
     */
    public function detectLanguageFromString(string $input): string;

    /**
     * @return array
     */
    public function getSupportedMimeTypes(): array;

    /**
     * Public method to check the availability of this service.
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
