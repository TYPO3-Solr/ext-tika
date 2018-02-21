<?php

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class PreviewControllerTest extends UnitTestCase {

    /**
     * @test
     */
    public function previewActionTriggersTikaServices()
    {
            /** @var $controller PreviewController */
        $controller = $this->getMockBuilder(PreviewController::class)->setMethods([
            'getFileResourceFactory', 'getInitializedPreviewView', 'getConfiguredTikaService', 'getIsAdmin'
        ])->getMock();

        $fileMock = $this->getMockBuilder(FileInterface::class)->getMock();
        $fileResourceFactoryMock = $this->getMockBuilder(ResourceFactory::class)->getMock();
        $fileResourceFactoryMock->expects($this->once())->method('getFileObjectFromCombinedIdentifier')->willReturn($fileMock);
        $controller->expects($this->once())->method('getFileResourceFactory')->willReturn($fileResourceFactoryMock);


        $serviceMock = $this->getMockBuilder(ServerService::class)->disableOriginalConstructor()->getMock();
        $serviceMock->expects($this->once())->method('extractText')->with($fileMock)->willReturn('Extracted Text');
        $serviceMock->expects($this->once())->method('extractMetaData')->with($fileMock)->willReturn(['metaKey' => 'metaValue']);
        $serviceMock->expects($this->once())->method('detectLanguageFromFile')->with($fileMock)->willReturn('de');

        $controller->expects($this->once())->method('getIsAdmin')->willReturn(true);
        $controller->expects($this->once())->method('getConfiguredTikaService')->willReturn($serviceMock);


        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $bodyMock = $this->getMockBuilder(StreamInterface::class)->getMock();
        $response->expects($this->once())->method('getBody')->willReturn($bodyMock);

        $controller->previewAction($request, $response);
    }

    /**
     * @test
     */
    public function previewActionShowsErrorWhenNoAdmin()
    {
        /** @var $controller PreviewController */
        $controller = $this->getMockBuilder(PreviewController::class)->setMethods([
            'getFileResourceFactory', 'getInitializedPreviewView', 'getConfiguredTikaService', 'getIsAdmin'
        ])->getMock();
        $controller->expects($this->once())->method('getIsAdmin')->willReturn(false);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $bodyMock = $this->getMockBuilder(StreamInterface::class)->getMock();
        $bodyMock->expects($this->once())->method('write')->with('Only admins can see the tika preview');
        $response->expects($this->once())->method('getBody')->willReturn($bodyMock);

        $controller->previewAction($request, $response);
    }
}