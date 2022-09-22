<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule;

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

use ApacheSolrForTypo3\Solr\Controller\Backend\Search\AbstractModuleController;
use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use ApacheSolrForTypo3\Tika\Util;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\View\ViewInterface;

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
    protected array $tikaConfiguration = [];

    /**
     * @var AbstractService|AppService|ServerService|SolrCellService
     */
    protected $tikaService;

    /**
     * Can be used in the test context to mock a {@link view}.
     *
     * Purpose: PhpUnit
     *
     * @param ViewInterface $view
     */
    public function overwriteView(ViewInterface $view): void
    {
        $this->view = $view;
    }

    /**
     * Can be used in the test context to mock a {@link moduleTemplate}.
     *
     * Purpose: PhpUnit
     *
     * @param ModuleTemplate $moduleTemplate
     */
    public function overwriteModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $this->moduleTemplate = $moduleTemplate;
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
     * @noinspection PhpUnused
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();
        $tikaConfiguration = Util::getTikaExtensionConfiguration();
        $this->setTikaConfiguration($tikaConfiguration);
        $this->tikaService = ServiceFactory::getTika($this->tikaConfiguration['extractor'] ?? '');
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
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    public function indexAction(): ResponseInterface
    {
        $this->view->assign('configuration', $this->tikaConfiguration);
        $this->view->assign(
            'extractor',
            ucfirst($this->tikaConfiguration['extractor'] ?? '')
        );

        if ($this->tikaConfiguration['extractor'] === 'server') {
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
        return $this->getModuleTemplateResponse();
    }

    /**
     * Starts the Tika server
     * @noinspection PhpUnused
     */
    public function startServerAction(): ResponseInterface
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

        return new RedirectResponse($this->uriBuilder->uriFor('index'), 303);
    }

    /**
     * Stops the Tika server
     * @noinspection PhpUnused
     */
    public function stopServerAction(): ResponseInterface
    {
        $this->tikaService->/** @scrutinizer ignore-call */ stopServer();

        // give it some time to stop
        sleep(2);

        if (!$this->tikaService->/** @scrutinizer ignore-call */ isServerRunning()) {
            $this->addFlashMessage(
                'Tika server stopped.',
                FlashMessage::OK
            );
        }

        return new RedirectResponse($this->uriBuilder->uriFor('index'), 303);
    }

    /**
     * Gets the Tika server version
     *
     * @return string Tika server version string
     * @throws ClientExceptionInterface
     * @throws Throwable
     */
    protected function getTikaServerVersion(): string
    {
        return $this->tikaService->getTikaVersion();
    }

    /**
     * Tries to connect to Tika server
     *
     * @return bool TRUE if the Tika server responds, FALSE otherwise.
     */
    protected function isTikaServerRunning(): bool
    {
        return $this->tikaService->/** @scrutinizer ignore-call */ isServerRunning();
    }

    /**
     * Returns the pid if the Tika server has been started through the backend
     * module.
     *
     * @return int|null Tika Server pid or null if not found
     */
    protected function getTikaServerPid(): ?int
    {
        return $this->tikaService->/** @scrutinizer ignore-call */getServerPid();
    }

    /**
     * Checks whether the server jar has been configured properly.
     *
     * @return bool TRUE if a path for the jar has been configured and the path exists
     */
    protected function isTikaServerJarAvailable(): bool
    {
        if (!empty($this->tikaConfiguration['tikaServerPath'])) {
            return file_exists($this->tikaConfiguration['tikaServerPath']);
        }
        return false;
    }

    /**
     * Checks whether Tika server can be controlled (started/stopped).
     *
     * Checks whether exec() is allowed and whether configuration is available.
     *
     * @return bool TRUE if Tika server can be started/stopped
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
