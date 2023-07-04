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

namespace ApacheSolrForTypo3\Tika\Tests\Integration\Service\Tika\Fixtures;

use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Class ServerServiceFixture
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ServerServiceFixture extends ServerService
{
    /**
     * The endpoint to be used
     *
     * @var string
     */
    protected string $recordedEndpoint = '';

    /**
     * @return string
     */
    public function getRecordedEndpoint(): string
    {
        return $this->recordedEndpoint;
    }

    /**
     * Override endpoint method in order to validate the correct endpoint is in use
     *
     * @param string $endpoint
     * @return Uri
     */
    protected function createEndpoint(string $endpoint): Uri
    {
        $this->recordedEndpoint = $endpoint;
        return parent::createEndpoint($endpoint);
    }

    /**
     * @param FileInterface $file
     * @param string $response
     * @return array
     */
    protected function getLogData(FileInterface $file, string $response): array
    {
        //overwrite to skip logging in unit test
        return [];
    }
}
