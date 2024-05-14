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

namespace ApacheSolrForTypo3\Tika\Tests\Unit\Backend\SolrModule;

use ApacheSolrForTypo3\Tika\Controller\Backend\SolrModule\TikaControlPanelModuleController;
use ApacheSolrForTypo3\Tika\Service\Tika\ServerService;
use ApacheSolrForTypo3\Tika\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface as HttpClientExceptionInterface;
use Throwable;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Class
 *
 * @author Timo Hund <timo.hund@dkd.de>
 */
class TikaControlPanelModuleControllerTest extends UnitTestCase
{
    protected TikaControlPanelModuleController $controller;

    protected ViewInterface|MockObject $viewMock;

    protected ModuleTemplate|MockObject $moduleTemplateMock;

    /**
     * @throws MockObjectException
     */
    public function setUp(): void
    {
        $this->viewMock = $this->createMock(ViewInterface::class);
        /** @noinspection PhpUnitInvalidMockingEntityInspection See: dg/bypass-finals */
        $this->moduleTemplateMock = $this->createMock(ModuleTemplate::class);

        $this->controller = $this->createPartialMock(
            TikaControlPanelModuleController::class,
            [
                'getFlashMessageQueue',
                'getModuleTemplateResponse',
            ]
        );
        $this->controller->overwriteModuleTemplate($this->moduleTemplateMock);
        $this->controller->overwriteView($this->viewMock);
        parent::setUp();
    }

    /**
     * Can the controller render the information from the tika server service.
     *
     * @throws HttpClientExceptionInterface
     * @throws Throwable
     */
    #[Test]
    public function canShowInformationFromStandaloneTikaServer(): void
    {
        /** @var ServerService|MockObject $tikaServerService */
        $tikaServerService = $this->getMockBuilder(ServerService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getServerPid',
                'getTikaServerUri',
                'getTikaServerUrl',
                'getTikaVersion',
                'getTikaVersionString',
                'isServerRunning',
                'ping',
            ])
            ->getMock();
        $tikaServerService->expects(self::atLeastOnce())->method('ping')->willReturn(true);
        $tikaServerService->expects(self::atLeastOnce())->method('isServerRunning')->willReturn(true);
        $tikaServerService->expects(self::atLeastOnce())->method('getServerPid')->willReturn(4711);
        $tikaServerService->expects(self::atLeastOnce())->method('getTikaVersion')->willReturn('1.11');
        $tikaServerService->expects(self::atLeastOnce())->method('getTikaVersionString')->willReturn('1.11');

        $this->controller->setTikaService($tikaServerService);
        $tikaConfiguration = [
            'extractor' => 'server',
            'tikaServerPath' => $this->getFixturePath('fake-server-jar.jar'),
        ];
        $this->controller->setTikaConfiguration($tikaConfiguration);
        $matcher = self::any();

        $this->viewMock->expects($matcher)->method('assign')->willReturnCallback(function() use ($matcher, $tikaConfiguration) {
            return match ($matcher->numberOfInvocations()) {
                1 => [ 'configuration', $tikaConfiguration ],
                2 => [ 'extractor', ucfirst($tikaConfiguration['extractor']) ],
                3 => [ 'server',
                    [
                        'isConnected' => true,
                        'jarAvailable' => true,
                        'isRunning' => true,
                        'isControllable' => true,
                        'pid' => 4711,
                        'version' => '1.11',
                    ],
                ],
            };
        });

        $this->controller->indexAction();
    }
}
