<?php

declare(strict_types=1);
namespace ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule;

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

use ApacheSolrForTypo3\Solr\Controller\Backend\Search\AbstractModuleController;
use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use ApacheSolrForTypo3\Tika\Util;
use Exception;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Tika Control Panel
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class TikaControlPanelModuleController extends AbstractModuleController
{

    /**
     * Tika configuration
     *
     * @var array
     */
    protected $tikaConfiguration = [];

    /**
     * @var AppService|ServerService|SolrCellService
     */
    protected $tikaService;

    /**
     * Can be used in the test context to force a view.
     *
     * @param ViewInterface $view
     */
    public function overwriteView(ViewInterface $view): void
    {
        $this->view = $view;
    }

    /**
     * @param AbstractService $tikaService
     */
    public function setTikaService(AbstractService $tikaService): void
    {
        $this->tikaService = $tikaService;
    }

    /**
     * Initializes resources commonly needed for several actions.
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();

        $tikaConfiguration = Util::getTikaExtensionConfiguration();
        $this->setTikaConfiguration($tikaConfiguration);
        $this->tikaService = ServiceFactory::getTika($this->tikaConfiguration['extractor']);
    }

    /**
     * @param array $tikaConfiguration
     */
    public function setTikaConfiguration(array $tikaConfiguration): void
    {
        $this->tikaConfiguration = $tikaConfiguration;
    }

    /**
     * Index action
     *
     * @throws Exception
     */
    public function indexAction(): void
    {
        $this->view->assign('configuration', $this->tikaConfiguration);
        $this->view->assign(
            'extractor',
            ucfirst($this->tikaConfiguration['extractor'])
        );

        if ($this->tikaConfiguration['extractor'] == 'server') {
            $this->checkTikaServerConnection();

            $this->view->assign(
                'server',
                [
                    'jarAvailable' => $this->isTikaServerJarAvailable(),
                    'isRunning' => $this->isTikaServerRunning(),
                    'isControllable' => $this->isTikaServerControllable(),
                    'pid' => $this->getTikaServerPid(),
                    'version' => $this->getTikaServerVersion(),
                ]
            );
        }
    }

    /**
     * Starts the Tika server
     *
     * @throws StopActionException
     */
    public function startServerAction(): void
    {
        $this->tikaService->/** @scrutinizer ignore-call */startServer();

        // give it some time to start
        sleep(2);

        if ($this->tikaService->/** @scrutinizer ignore-call */isServerRunning()) {
            $this->addFlashMessage(
                'Tika server started.',
                FlashMessage::OK
            );
        }

        $this->redirect('index');
    }

    /**
     * Stops the Tika server
     *
     * @throws StopActionException
     */
    public function stopServerAction(): void
    {
        $this->tikaService->/** @scrutinizer ignore-call */stopServer();

        // give it some time to stop
        sleep(2);

        if (!$this->tikaService->isServerRunning()) {
            $this->addFlashMessage(
                'Tika server stopped.',
                FlashMessage::OK
            );
        }

        $this->redirect('index');
    }

    /**
     * Gets the Tika server version
     *
     * @return string Tika server version string
     * @throws Exception
     */
    protected function getTikaServerVersion()
    {
        return $this->tikaService->getTikaVersion();
    }

    /**
     * Tries to connect to Tika server
     *
     * @return bool TRUE if the Tika server responds, FALSE otherwise.
     * @throws Exception
     */
    protected function isTikaServerRunning()
    {
        return $this->tikaService->isServerRunning();
    }

    /**
     * Returns the pid if the Tika server has been started through the backend
     * module.
     *
     * @return int|null Tika Server pid or null if not found
     */
    protected function getTikaServerPid()
    {
        return $this->tikaService->/** @scrutinizer ignore-call */getServerPid();
    }

    /**
     * Checks whether the server jar has been configured properly.
     *
     * @return bool TRUE if a path for the jar has been configure and the path exists
     */
    protected function isTikaServerJarAvailable()
    {
        $serverJarSet = !empty($this->tikaConfiguration['tikaServerPath']);
        $serverJarExists = file_exists($this->tikaConfiguration['tikaServerPath']);

        return $serverJarSet && $serverJarExists;
    }

    /**
     * Checks whether Tika server can be controlled (started/stopped).
     *
     * Checks whether exec() is allowed and whether configuration is available.
     *
     * @return bool TRUE if Tika server can be started/stopped
     * @throws Exception
     */
    protected function isTikaServerControllable(): bool
    {
        $disabledFunctions = ini_get('disable_functions')
            . ',' . ini_get('suhosin.executor.func.blacklist');
        $disabledFunctions = GeneralUtility::trimExplode(
            ',',
            $disabledFunctions
        );
        if (in_array('exec', $disabledFunctions)) {
            return false;
        }

        if (ini_get('safe_mode')) {
            return false;
        }

        $jarAvailable = $this->isTikaServerJarAvailable();
        $running = $this->isTikaServerRunning();
        $pid = $this->getTikaServerPid();

        $controllable = false;
        if ($running && $jarAvailable && !is_null($pid)) {
            $controllable = true;
        } elseif (!$running && $jarAvailable) {
            $controllable = true;
        }

        return $controllable;
    }

    /**
     * Checks whether the configured Tika server can be reached and provides a
     * flash message according to the result of the check.
     *
     * @throws Exception
     */
    protected function checkTikaServerConnection(): void
    {
        if ($this->tikaService->/** @scrutinizer ignore-call */ping()) {
            $this->addFlashMessage(
                'Tika host contacted at: ' . $this->tikaService->/** @scrutinizer ignore-call */getTikaServerUrl(),
                'Your Apache Tika server has been contacted.',
                FlashMessage::OK
            );
        } else {
            $this->addFlashMessage(
                'Could not connect to Tika at: ' . $this->tikaService->/** @scrutinizer ignore-call */getTikaServerUrl(),
                'Unable to contact Apache Tika server.',
                FlashMessage::ERROR
            );
        }
    }
}
