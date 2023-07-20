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

namespace ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule;

use ApacheSolrForTypo3\Solr\Controller\Backend\Search\AbstractModuleController;
use ApacheSolrForTypo3\Solr\Domain\Site\Exception\UnexpectedTYPO3SiteInitializationException;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use ApacheSolrForTypo3\Tika\Util;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Tika Control Panel
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class TikaControlPanelModuleController extends AbstractModuleController
{
    protected array $tikaConfiguration = [];

    protected ServerService|AppService|SolrCellService $tikaService;

    /**
     * Can be used in the test context to mock a {@link view}.
     *
     * Purpose: PhpUnit
     */
    public function overwriteView(ViewInterface $view): void
    {
        $this->view = $view;
    }

    /**
     * Can be used in the test context to mock a {@link moduleTemplate}.
     *
     * Purpose: PhpUnit
     */
    public function overwriteModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $this->moduleTemplate = $moduleTemplate;
    }

    public function setTikaService(ServerService|AppService|SolrCellService $tikaService): void
    {
        $this->tikaService = $tikaService;
    }

    /**
     * Initializes resources commonly needed for several actions.
     *
     * @throws DBALException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws UnexpectedTYPO3SiteInitializationException
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();
        $tikaConfiguration = Util::getTikaExtensionConfiguration();
        $this->setTikaConfiguration($tikaConfiguration);
        $this->tikaService = ServiceFactory::getTika($this->tikaConfiguration['extractor'] ?? '');
    }

    public function setTikaConfiguration(array $tikaConfiguration): void
    {
        $this->tikaConfiguration = $tikaConfiguration;
    }

    /**
     * Index action
     *
     * @throws ClientExceptionInterface
     * @throws Throwable
     */
    public function indexAction(): ResponseInterface
    {
        $this->view->assign('configuration', $this->tikaConfiguration);
        $this->view->assign(
            'extractor',
            ucfirst($this->tikaConfiguration['extractor'] ?? '')
        );

        switch ($this->tikaConfiguration['extractor']) {
            case 'server':
                $this->view->assign(
                    'server',
                    [
                        'isConnected' => $this->isConnectedToTikaServer(),
                        'jarAvailable' => $this->isTikaServerJarAvailable(),
                        'isRunning' => $this->isTikaServerRunning(),
                        'isControllable' => $this->isTikaServerControllable(),
                        'pid' => $this->getTikaServerPid(),
                        'version' => $this->getTikaServerVersion(),
                    ]
                );
                break;
            case 'solr':
                $this->view->assign(
                    'solr',
                    [
                        'isConnected' => $this->isConnectedToTikaServer(),
                        'version' => $this->getTikaServerVersion(),
                    ]
                );
                break;
            case 'jar':
                $this->view->assign(
                    'jar',
                    [
                        'version' => $this->getTikaServerVersion(),
                    ]
                );
                break;
            default:
        }

        return $this->getModuleTemplateResponse();
    }

    /**
     * Starts the Tika server
     *
     * @noinspection PhpUnused
     */
    public function startServerAction(): ResponseInterface
    {
        $this->tikaService->startServer();

        // give it some time to start
        sleep(2);

        if ($this->tikaService->isServerRunning()) {
            $this->addFlashMessage(
                'Tika server started.',
            );
        }

        return new RedirectResponse($this->uriBuilder->uriFor('index'), 303);
    }

    /**
     * Stops the Tika server
     *
     * @noinspection PhpUnused
     */
    public function stopServerAction(): ResponseInterface
    {
        $this->tikaService->stopServer();

        // give it some time to stop
        sleep(2);

        if (!$this->tikaService->isServerRunning()) {
            $this->addFlashMessage(
                '',
                'Tika server stopped.',
            );
        }

        return new RedirectResponse($this->uriBuilder->uriFor('index'), 303);
    }

    /**
     * Gets the Tika server version
     *
     * @return string Tika server version string
     */
    protected function getTikaServerVersion(): string
    {
        return $this->tikaService->getTikaVersionString();
    }

    /**
     * Tries to connect to Tika server
     *
     * @return bool TRUE if the Tika server responds, FALSE otherwise.
     */
    protected function isTikaServerRunning(): bool
    {
        return $this->tikaService->isServerRunning();
    }

    /**
     * Returns the pid if the Tika server has been started through the backend
     * module.
     *
     * @return int|null Tika Server pid or null if not found
     */
    protected function getTikaServerPid(): ?int
    {
        return $this->tikaService->getServerPid();
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
     *
     * @throws ClientExceptionInterface
     * @throws Throwable
     */
    protected function isConnectedToTikaServer(): bool
    {
        if ($this->tikaService->ping()) {
            $this->addFlashMessage(
                'Tika host contacted at: ' . $this->tikaService->getTikaServerUrl(),
                'Your Apache Tika ' . $this->tikaService->getTikaVersion() . ' server has been contacted.',
            );
            return true;
        }
        $this->addFlashMessage(
            'Could not connect to Tika at: ' . $this->tikaService->getTikaServerUrl(),
            'Unable to contact Apache Tika server.',
            ContextualFeedbackSeverity::ERROR
        );
        return false;
    }
}
