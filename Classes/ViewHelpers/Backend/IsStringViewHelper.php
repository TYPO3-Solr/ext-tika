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

namespace ApacheSolrForTypo3\Tika\ViewHelpers\Backend;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * Condition for checking if type is a string.
 *
 * @todo: Find TYPO3/Fluid core way for that trouble and reuse it on {@link \ApacheSolrForTypo3\Solr\ViewHelpers\Backend\IsStringViewHelper}
 */
class IsStringViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initialize ViewHelper arguments
     *
     * @noinspection PhpUnused
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'mixed', 'Value to be verified.', true);
    }

    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        return is_string($arguments['value'] ?? null);
    }
}
