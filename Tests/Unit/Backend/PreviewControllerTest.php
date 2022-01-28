<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Backend;

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

use ApacheSolrForTypo3\Tika\Controller\Backend\PreviewController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Class PreviewControllerTest
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class PreviewControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function previewActionTriggersTikaServices(): void
    {
        /** @var $controller PreviewController|MockObject */
        $controller = $this->getMockBuilder(PreviewController::class)->onlyMethods([
            'getFileResourceFactory',
            'getInitializedPreviewView',
            'getConfiguredTikaService',
            'getIsAdmin',
        ])->getMock();

        $fileMock = $this->getMockBuilder(FileInterface::class)->getMock();
        $fileResourceFactoryMock = $this->getMockBuilder(ResourceFactory::class)->disableOriginalConstructor()->getMock();
        $fileResourceFactoryMock->expects(self::once())->method('getFileObjectFromCombinedIdentifier')->willReturn($fileMock);
        $controller->expects(self::once())->method('getFileResourceFactory')->willReturn($fileResourceFactoryMock);

        $serviceMock = $this->getMockBuilder(ServerService::class)->disableOriginalConstructor()->getMock();
        $serviceMock->expects(self::once())->method('extractText')->with($fileMock)->willReturn('Extracted Text');
        $serviceMock->expects(self::once())->method('extractMetaData')->with($fileMock)->willReturn(['metaKey' => 'metaValue']);
        $serviceMock->expects(self::once())->method('detectLanguageFromFile')->with($fileMock)->willReturn('de');

        $controller->expects(self::once())->method('getIsAdmin')->willReturn(true);
        $controller->expects(self::once())->method('getConfiguredTikaService')->willReturn($serviceMock);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->expects(self::once())->method('getQueryParams')->willReturn(self::returnValue(['identifier' => '']));
        $controller->previewAction($request);
    }

    /**
     * @test
     */
    public function previewActionShowsErrorWhenNoAdmin(): void
    {
        /** @var $controller PreviewController|MockObject */
        $controller = $this->getMockBuilder(PreviewController::class)->onlyMethods([
            'getFileResourceFactory',
            'getInitializedPreviewView',
            'getConfiguredTikaService',
            'getIsAdmin',
        ])->getMock();
        $controller->expects(self::once())->method('getIsAdmin')->willReturn(false);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        /* @var Response $response */
        $response = $controller->previewAction($request);
        self::assertEquals(403, $response->getStatusCode(), 'Non admin BE users do not get 403.');
        self::assertEquals('Only admins can see the tika preview', $response->getBody(), 'Non admin user do not get proper forbidden message.');
    }
}
