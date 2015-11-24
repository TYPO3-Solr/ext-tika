<?php
namespace ApacheSolrForTypo3\Tika\Backend\SolrModule;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Ingo Renner <ingo@typo3.org>
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

use ApacheSolrForTypo3\Solr\Backend\SolrModule\AbstractModuleController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;


/**
 * Tika Control Panel
 *
 * @package ApacheTikaForTypo3\Tika\Backend\Module
 * @author Ingo Renner <ingo@typo3.org>
 */
class TikaControlPanelModuleController extends AbstractModuleController
{

    /**
     * Module name, used to identify a module f.e. in URL parameters.
     *
     * @var string
     */
    protected $moduleName = 'TikaControlPanel';

    /**
     * Module title, shows up in the module menu.
     *
     * @var string
     */
    protected $moduleTitle = 'Tika';

    /**
     * Tika configuration
     *
     * @var array
     */
    protected $tikaConfiguration = array();

    /**
     * @var \ApacheSolrForTypo3\Tika\Service\Tika\AbstractService
     */
    protected $tikaService = null;

    /**
     * Can be used in the test context to force a view.
     *
     * @param ViewInterface
     */
    public function overwriteView(ViewInterface $view) {
        $this->view = $view;
    }

    /**
     * Initializes resources commonly needed for several actions.
     *
     * @return void
     */
    protected function initializeAction()
    {
        parent::initializeAction();

        $tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
        $this->setTikaConfiguration($tikaConfiguration);
        $this->setTikaService(ServiceFactory::getTika($this->tikaConfiguration['extractor']));
    }

    /**
     * @param ServiceInterface $tikaService
     */
    public function setTikaService(ServiceInterface $tikaService) {
       $this->tikaService = $tikaService;
    }

    /**
     * @param array $tikaConfiguration
     */
    public function setTikaConfiguration(array $tikaConfiguration) {
        $this->tikaConfiguration = $tikaConfiguration;
    }

    /**
     * Index action
     *
     * @return void
     * @throws \Exception
     */
    public function indexAction()
    {
        $this->checkTikaAvailability();
        $this->view->assign('configuration', $this->tikaConfiguration);
        $this->view->assign('extractor',
            ucfirst($this->tikaConfiguration['extractor']));

        $this->view->assign('tikaService', $this->tikaService);
    }

    /**
     * Starts the Tika server
     *
     * @return void
     */
    public function startServerAction()
    {
        $this->tikaService->startServer();

        // give it some time to start
        sleep(2);

        if ($this->tikaService->getIsServerRunning()) {
            $this->addFlashMessage(
                'Tika server started.',
                FlashMessage::OK
            );
        }

        $this->forwardToIndex();
    }

    /**
     * Stops the Tika server
     *
     * @return void
     */
    public function stopServerAction()
    {
        $this->tikaService->stopServer();

        // give it some time to stop
        sleep(2);

        if (!$this->tikaService->getIsServerRunning()) {
            $this->addFlashMessage(
                'Tika server stopped.',
                FlashMessage::OK
            );
        }

        $this->forwardToIndex();
    }

    /**
     * Checks whether the configured Tika server can be reached and provides a
     * flash message according to the result of the check.
     *
     * @return void
     */
    protected function checkTikaAvailability()
    {
        if ($this->tikaService->getIsAvailable()) {
            $this->addFlashMessage(
                'Tika is up and running with endpoint: ' . $this->tikaService->getEndpointIdentifier(),
                'Contacted configured tika service endpoint.',
                FlashMessage::OK
            );
        } else {
            $this->addFlashMessage(
                'Could not connect tika endpoint at: ' . $this->tikaService->getEndpointIdentifier(),
                'Unable to contact your tika service endpoint.',
                FlashMessage::ERROR
            );
        }
    }

}
