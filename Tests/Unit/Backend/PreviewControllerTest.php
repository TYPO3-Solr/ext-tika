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

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Backend;

use ApacheSolrForTypo3\Tika\Controller\Backend\PreviewController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PreviewControllerTest
 */
class PreviewControllerTest extends UnitTestCase
{
    /**
     * @throws Throwable
     * @throws MockObjectException
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function previewActionTriggersTikaServices(): void
    {
        /** @var PreviewController|MockObject $controller */
        $controller = $this->getMockBuilder(PreviewController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getConfiguredTikaService',
                'getFileResourceFactory',
                'getInitializedPreviewView',
                'getIsAdmin',
            ])
            ->getMock();

        $fileMock = $this->createMock(FileInterface::class);
        $fileResourceFactoryMock = $this->createMock(ResourceFactory::class);
        $fileResourceFactoryMock->expects(self::once())->method('getFileObjectFromCombinedIdentifier')->willReturn($fileMock);

        $controller->expects(self::once())->method('getFileResourceFactory')->willReturn($fileResourceFactoryMock);

        $serviceMock = $this->createMock(ServerService::class);
        $serviceMock->expects(self::once())->method('extractText')->with($fileMock)->willReturn('Extracted Text');
        $serviceMock->expects(self::once())->method('extractMetaData')->with($fileMock)->willReturn(['metaKey' => 'metaValue']);
        $serviceMock->expects(self::once())->method('detectLanguageFromFile')->with($fileMock)->willReturn('de');

        $controller->expects(self::once())->method('getIsAdmin')->willReturn(true);
        $controller->expects(self::once())->method('getConfiguredTikaService')->willReturn($serviceMock);
        $controller->expects(self::once())->method('getInitializedPreviewView')->willReturn($this->createMock(StandaloneView::class));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getQueryParams')->willReturn(['identifier' => '']);

        $controller->previewAction($request);
    }

    /**
     * @throws Throwable
     * @throws MockObjectException
     * @throws ClientExceptionInterface
     */
    #[Test]
    public function previewActionShowsErrorWhenNoAdmin(): void
    {
        /** @var PreviewController|MockObject $controller */
        $controller = $this->getMockBuilder(PreviewController::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getIsAdmin',
            ])
            ->getMock();
        $controller->expects(self::once())->method('getIsAdmin')->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);

        /** @var Response $response */
        $response = $controller->previewAction($request);
        self::assertEquals(403, $response->getStatusCode(), 'Non admin BE users do not get 403.');
        self::assertEquals('Only admins can see the tika preview', $response->getBody(), 'Non admin user do not get proper forbidden message.');
    }
}
