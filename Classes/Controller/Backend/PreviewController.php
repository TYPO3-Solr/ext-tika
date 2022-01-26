<?php

declare(strict_types=1);
namespace ApacheSolrForTypo3\Tika\Controller\Backend;

use ApacheSolrForTypo3\Tika\Service\Tika\AbstractService;
use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PreviewController
 */
class PreviewController
{

    /**
     * @param ServerRequestInterface $request
     * @return string|Response
     * @throws Exception
     */
    public function previewAction(ServerRequestInterface $request)
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
        $metadata = $tikaService->extractMetaData($file);
        $content = $tikaService->extractText($file);

        try {
            $language = $tikaService->detectLanguageFromFile($file);
        } catch (Exception $e) {
            $language = 'not detectable';
        }

        $view = $this->getInitializedPreviewView();

        $view->assign('metadata', $metadata);
        $view->assign('content', $content);
        $view->assign('language', $language);

        $response->getBody()->write($view->render());

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
     * @throws InvalidExtensionNameException
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
    protected function getIsAdmin()
    {
        return (bool)$GLOBALS['BE_USER']->isAdmin();
    }
}
