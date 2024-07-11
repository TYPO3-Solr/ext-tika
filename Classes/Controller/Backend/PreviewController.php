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

namespace ApacheSolrForTypo3\Tika\Controller\Backend;

use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PreviewController
 */
class PreviewController
{
    /**
     * @throws ClientExceptionInterface
     * @throws Throwable
     */
    public function previewAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = new HtmlResponse('');
        if (!$this->getIsAdmin()) {
            $messageText = 'Only admins can see the tika preview';
            $response->getBody()->write($messageText);
            return $response->withStatus(403, $messageText);
        }

        $identifier = (string)$request->getQueryParams()['identifier'];
        $file = $this->getFileResourceFactory()->getFileObjectFromCombinedIdentifier($identifier);

        $tikaService = $this->getConfiguredTikaService();
        $metadata = $tikaService->extractMetaData(
            /** not real static-analysis error, because checked in {@link \ApacheSolrForTypo3\Tika\ContextMenu\Preview::canHandle()} */
            $file
        );
        $content = $tikaService->extractText(
            /** not real static-analysis error, because checked in {@link \ApacheSolrForTypo3\Tika\ContextMenu\Preview::canHandle()} */
            $file
        );

        try {
            $language = $tikaService->detectLanguageFromFile(
                /** not real static-analysis error, because checked in {@link \ApacheSolrForTypo3\Tika\ContextMenu\Preview::canHandle()} */
                $file
            );
        } catch (Throwable) {
            $language = 'not detectable';
        }

        $view = $this->getInitializedPreviewView();

        $view->assign('metadata', $metadata);
        $view->assign('content', $content);
        $view->assign('language', $language);

        $response->getBody()->write($view->render() ?? '');

        return $response;
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    protected function getConfiguredTikaService(): AbstractService|AppService|ServerService|SolrCellService
    {
        return ServiceFactory::getConfiguredTika();
    }

    protected function getFileResourceFactory(): ResourceFactory
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    protected function getInitializedPreviewView(): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templatePathAndFile = 'EXT:tika/Resources/Private/Templates/Backend/Preview.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFile));
        return $view;
    }

    protected function getIsAdmin(): bool
    {
        return !empty($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->isAdmin();
    }
}
