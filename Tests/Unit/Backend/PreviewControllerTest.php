<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Timo Hund <timo.hund@dkd.de>
 *
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

use ApacheSolrForTypo3\Tika\Controller\Backend\PreviewController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class PreviewControllerTest extends UnitTestCase
{

    /**
     * @test
     */
    public function previewActionTriggersTikaServices(): void
    {
        /** @var $controller PreviewController */
        $controller = $this->getMockBuilder(PreviewController::class)->setMethods([
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
        $controller->previewAction($request);
    }

    /**
     * @test
     */
    public function previewActionShowsErrorWhenNoAdmin(): void
    {
        /** @var $controller PreviewController */
        $controller = $this->getMockBuilder(PreviewController::class)->setMethods([
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
