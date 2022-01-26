<?php

declare(strict_types=1);
namespace ApacheSolrForTypo3\Tika\Tests\Unit\Backend\SolrModule;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Timo Schmidt <timo.schmidt@dkd.de>
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

use ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule\TikaControlPanelModuleController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

class TikaControlPanelModuleControllerTest extends UnitTestCase
{
    /**
     * @var TikaControlPanelModuleController
     */
    protected $controller;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
     */
    protected $viewMock;

    public function setUp(): void
    {
        $this->viewMock = $this->getDumbMock(ViewInterface::class);
        $this->controller = $this->getMockBuilder(TikaControlPanelModuleController::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFlashMessage'])
            ->getMock();
        $this->controller->overwriteView($this->viewMock);
    }

    /**
     * Can the controller render the information from the tika server service.
     *
     * @test
     */
    public function canShowInformationFromStandaloneTikaServer(): void
    {
        $tikaServerService = $this->getDumbMock(ServerService::class);
        $tikaServerService->expects(self::atLeastOnce())->method('isServerRunning')->willReturn(true);
        $tikaServerService->expects(self::atLeastOnce())->method('getServerPid')->willReturn(4711);
        $tikaServerService->expects(self::atLeastOnce())->method('getTikaVersion')->willReturn('1.11');

        $this->controller->setTikaService($tikaServerService);
        $this->controller->setTikaConfiguration([
            'extractor' => 'server',
            'tikaServerPath' => $this->getFixturePath('fake-server-jar.jar'),
        ]);

        $this->viewMock->expects(self::at(2))->method('assign')->with(
            'server',
            [
                'jarAvailable' => true,
                'isRunning' => true,
                'isControllable' => true,
                'pid' => 4711,
                'version' => '1.11',
            ]
        );

        $this->controller->indexAction();
    }
}
