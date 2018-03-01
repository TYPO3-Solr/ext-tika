<?php
namespace ApacheSolrForTypo3\Tika\Controller\Backend;

use ApacheSolrForTypo3\Tika\Service\Tika\AppService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Service\Tika\ServiceFactory;
use ApacheSolrForTypo3\Tika\Service\Tika\SolrCellService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class PreviewController
 * @package ApacheSolrForTypo3\Tika\Controller\Backend
 */
class PreviewController {

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return string
     */
    public function previewAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$this->getIsAdmin()) {
            $response->getBody()->write('Only admins can see the tika preview');
            return $response;
        }

        $identifier = (string)$request->getQueryParams()['identifier'];
        $file = $this->getFileResourceFactory()->getFileObjectFromCombinedIdentifier($identifier);

        $tikaService = $this->getConfiguredTikaService();
        $metadata = $tikaService->extractMetaData($file);
        $content = $tikaService->extractText($file);

        try {
            $language = $tikaService->detectLanguageFromFile($file);
        } catch (\Exception $e) {
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
    protected function getConfiguredTikaService()
    {
        return ServiceFactory::getConfiguredTika();
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected function getFileResourceFactory(): ResourceFactory
    {
        return \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
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
     * @return boolean
     */
    protected function getIsAdmin()
    {
        return (bool)$GLOBALS['BE_USER']->isAdmin();
    }
}