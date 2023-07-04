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

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Backend\SolrModule;

use ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule\TikaControlPanelModuleController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Class
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class TikaControlPanelModuleControllerTest extends UnitTestCase
{
    /**
     * @var TikaControlPanelModuleController
     */
    protected $controller;

    /**
     * @var ViewInterface
     */
    protected $viewMock;

    /**
     * @var ModuleTemplate|MockObject
     */
    protected $moduleTemplateMock;

    public function setUp(): void
    {
        $this->viewMock = $this->getDumbMock(ViewInterface::class);
        $this->moduleTemplateMock = $this->getDumbMock(ModuleTemplate::class);

        $this->controller = $this->getMockBuilder(TikaControlPanelModuleController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFlashMessage', 'getModuleTemplateResponse'])
            ->getMock();
        $this->controller->overwriteModuleTemplate($this->moduleTemplateMock);
        $this->controller->overwriteView($this->viewMock);
    }

    /**
     * Can the controller render the information from the tika server service.
     *
     * @test
     */
    public function canShowInformationFromStandaloneTikaServer(): void
    {
        /* @var ServerService|MockObject $tikaServerService */
        $tikaServerService = $this->getDumbMock(ServerService::class);
        $tikaServerService->expects(self::atLeastOnce())->method('isServerRunning')->willReturn(true);
        $tikaServerService->expects(self::atLeastOnce())->method('getServerPid')->willReturn(4711);
        $tikaServerService->expects(self::atLeastOnce())->method('getTikaVersion')->willReturn('1.11');

        $this->controller->setTikaService($tikaServerService);
        $tikaConfiguration = [
            'extractor' => 'server',
            'tikaServerPath' => $this->getFixturePath('fake-server-jar.jar'),
        ];
        $this->controller->setTikaConfiguration($tikaConfiguration);

        $this->viewMock->expects(self::any())->method('assign')->withConsecutive(
            [ 'configuration', $tikaConfiguration ],
            [ 'extractor', ucfirst($tikaConfiguration['extractor']) ],
            [ 'server',
                [
                    'jarAvailable' => true,
                    'isRunning' => true,
                    'isControllable' => true,
                    'pid' => 4711,
                    'version' => '1.11',
                ],
            ]
        );

        $this->controller->indexAction();
    }
}
