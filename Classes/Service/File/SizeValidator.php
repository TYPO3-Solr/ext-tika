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

namespace ApacheSolrForTypo3\Tika\Service\File;

use ApacheSolrForTypo3\Tika\Util;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Class SizeValidator
 */
class SizeValidator
{
    protected array $configuration;

    /**
     * Constructor
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct(array $extensionConfiguration = null)
    {
        $this->configuration = $extensionConfiguration ?? Util::getTikaExtensionConfiguration();
    }

    public function isBelowLimit(FileInterface $file): bool
    {
        return $file->getSize() < $this->getFileSizeLimit();
    }

    /**
     * Retrieves the size limit in byte when a text extraction on a file is done.
     *
     * Default value is 500MB.
     */
    protected function getFileSizeLimit(): int
    {
        // default is 500 MB
        $bytesPerMegaByte = 1048576;
        $textExtractMegaBytes = (int)$this->getConfigurationOrDefaultValue('fileSizeLimit', 500);
        return $textExtractMegaBytes * $bytesPerMegaByte;
    }

    protected function getConfigurationOrDefaultValue(
        string $key,
        mixed $defaultValue,
    ): mixed {
        return $this->configuration[$key] ?? $defaultValue;
    }
}
