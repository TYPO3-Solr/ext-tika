<?php
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


class TikaControlPanelModuleControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \ApacheSolrForTypo3\Tika\Backend\SolrModule\TikaControlPanelModuleController
     */
    protected $controllerMock;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\View\ViewInterface
     */
    protected $viewMock;

    /**
     * Returns a mocked object where all methods are mocked and it just "full fills" the object type.
     *
     * @param string $className
     */
    protected function getDumbMock($className) {
        return $this->getMock($className, array(), array(), '', FALSE);
    }

    public function setUp() {
        $this->viewMock = $this->getDumbMock('\TYPO3\CMS\Extbase\Mvc\View\ViewInterface');
        $this->controller = $this->getMock('\ApacheSolrForTypo3\Tika\Backend\SolrModule\TikaControlPanelModuleController', array('addFlashMessage'), array(), '', FALSE);
        $this->controller->overwriteView($this->viewMock);
    }

    /**
     * Can the controller render the information from the tika server service.
     *
     * @test
     */
    public function canShowInformationFromStandaloneTikaServer() {
        $tikaServerService = $this->getMock('ApacheSolrForTypo3\Tika\Service\Tika\ServerService', array(), array(), '', FALSE);
        $tikaServerService->expects($this->once())->method('isAvailable')->will($this->returnValue(TRUE));
        $this->controller->setTikaService($tikaServerService);
        $this->controller->indexAction();
    }

    /**
     * Can the controller render the information from the tika server service.
     *
     * @test
     */
    public function canShowInformationFromSolrCellService() {
        $tikaServerService = $this->getMock('ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService', array(), array(), '', FALSE);
        $this->controller->setTikaService($tikaServerService);
        $this->controller->indexAction();
    }

}
