<?php
namespace ApacheSolrForTypo3\Tika\Hooks;

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds Filelist related JavaScript to the backend
 */
class BackendControllerHook
{
    /**
     * Adds Filelist JavaScript used e.g. by context menu
     *
     * @param array $configuration
     * @param BackendController $backendController
     * @throws RouteNotFoundException
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function addJavaScript(array $configuration, BackendController $backendController)
    {
        $this->getPageRenderer()->addInlineSetting(
            'TikaPreview',
            'moduleUrl',
            (string)$this->getBackendUriBuilder()->buildUriFromRoute('tika_preview'));
    }

    /**
     * @return PageRenderer
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * @return UriBuilder
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function getBackendUriBuilder(): UriBuilder
    {
       return GeneralUtility::makeInstance(UriBuilder::class);
    }
}
