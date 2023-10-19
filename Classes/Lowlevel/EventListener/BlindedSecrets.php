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

namespace ApacheSolrForTypo3\Tika\Lowlevel\EventListener;

use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

use function str_contains;

class BlindedSecrets
{
    public function __invoke(ModifyBlindedConfigurationOptionsEvent $event): void
    {
        $options = $event->getBlindedConfigurationOptions();

        if ($event->getProviderIdentifier() === 'confVars') {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['solrUsername'])
                && !str_contains($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['solrUsername'], '%env(')
            ) {
                $options['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['solrUsername'] = '***';
            }
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['solrPassword'])
                && !str_contains($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['solrPassword'], '%env(')
            ) {
                $options['TYPO3_CONF_VARS']['EXTENSIONS']['tika']['solrPassword'] = '***';
            }
        }

        $event->setBlindedConfigurationOptions($options);
    }

    /**
     * Renders a single secret/credentials input field in BE -> Settings -> Extension Configuration -> tika
     * dependent on configured/saved value.
     * * \<input ... type="password"> for simple string, which masks the password in browser
     * * \<input ... type="text"> for values %env(SOME_SOLR_CREDENTIAL)%, which prints the value as is
     *
     * @noinspection PhpUnused
     */
    public function hideInExtConf(array $params): string
    {
        $currentConfigValue = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tika'][$params['fieldName']] ?? '';
        $inputType = 'password';
        if (empty($currentConfigValue)
            || str_contains($currentConfigValue, '%env(')
        ) {
            $inputType = 'text';
        }

        return /* @lang HTML */
            "<input class='form-control' id='em-tika-{$params['fieldName']}' type='$inputType' name='{$params['fieldName']}' value='$currentConfigValue'>";
    }
}
