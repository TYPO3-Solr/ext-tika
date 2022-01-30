<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Service\File;

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

use ApacheSolrForTypo3\Tika\Util;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Class SizeValidator
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class SizeValidator
{

    /**
     * @var array
     */
    protected array $configuration;

    /**
     * Constructor
     * @param array|null $extensionConfiguration
     */
    public function __construct(array $extensionConfiguration = null)
    {
        $this->configuration = $extensionConfiguration ?? Util::getTikaExtensionConfiguration();
    }

    /**
     * @param FileInterface $file
     * @return bool
     */
    public function isBelowLimit(FileInterface $file)
    {
        return $file->getSize() < $this->getFileSizeLimit();
    }

    /**
     * Retrieves the size limit in byte when a text extraction on a file is done.
     *
     * Default value is 500MB.
     *
     * @return int
     */
    protected function getFileSizeLimit()
    {
        // default is 500 MB
        $bytesPerMegaByte = 1048576;
        $textExtractMegaBytes = (int)$this->getConfigurationOrDefaultValue('fileSizeLimit', 500);
        return $textExtractMegaBytes * $bytesPerMegaByte;
    }

    /**
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getConfigurationOrDefaultValue(string $key, $defaultValue)
    {
        return $this->configuration[$key] ?? $defaultValue;
    }
}
