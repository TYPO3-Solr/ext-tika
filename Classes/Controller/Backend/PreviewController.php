<?php

declare(strict_types=1);

namespace ApacheSolrForTypo3\Tika\Controller\Backend;

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

use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PreviewController
 */
class PreviewController
{
    /**
     * @param ServerRequestInterface $request
     * @return string|Response
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
            /** @scrutinizer ignore-type because checked in {@link \ApacheSolrForTypo3\Tika\ContextMenu\Preview::canHandle()} */
            $file
        );
        $content = $tikaService->extractText(
            /** @scrutinizer ignore-type because checked in {@link \ApacheSolrForTypo3\Tika\ContextMenu\Preview::canHandle()} */
            $file
        );

        try {
            $language = $tikaService->detectLanguageFromFile(
                /** @scrutinizer ignore-type because checked in {@link \ApacheSolrForTypo3\Tika\ContextMenu\Preview::canHandle()} */
                $file
            );
        } catch (Throwable $e) {
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
     * @return AppService|ServerService|SolrCellService
     */
    protected function getConfiguredTikaService(): AbstractService
    {
        return ServiceFactory::getConfiguredTika();
    }

    /**
     * @return ResourceFactory
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function getFileResourceFactory(): ResourceFactory
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * @return StandaloneView
     */
    protected function getInitializedPreviewView(): StandaloneView
    {
        /** @var $view StandaloneView */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRequest()->setControllerExtensionName('tika');
        $templatePathAndFile = 'EXT:tika/Resources/Private/Templates/Backend/Preview.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFile));
        return $view;
    }

    /**
     * @return bool
     */
    protected function getIsAdmin(): bool
    {
        return !empty($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->isAdmin();
    }
}
