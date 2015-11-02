<?php
namespace ApacheSolrForTypo3\Tika\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2015 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ApacheSolrForTypo3\Tika\StatusCheck;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;


/**
 * Provides a status report about whether Tika is properly configured
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tika
 */
class TikaStatus implements StatusProviderInterface
{

    /**
     * EXT:tika configuration.
     *
     * @var array
     */
    protected $tikaConfiguration = array();

    /**
     * Checks whether Tika is properly configured
     *
     * TODO Check whether EXT:tika is installed AFTER EXT:solr
     */
    public function getStatus()
    {
        $reports = array();
        $tikaStatus = GeneralUtility::makeInstance('ApacheSolrForTypo3\\Tika\\StatusCheck');
        /* @var $tikaStatus StatusCheck */

        $status = GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status',
            'Apache Tika',
            'Configuration OK'
        );
        /* @var $status Status */

        if (!$tikaStatus->getStatus()) {
            $status = GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status',
                'Apache Tika',
                'Configuration Incomplete',
                '<p>Please check your configuration for Apache Tika.</p><p>
				Either use a local Tika jar binary app and make sure Java is
				available or use a remote Solr server\'s Extracting Request
				Handler.</p>',
                Status::ERROR
            );
        }

        $reports[] = $status;

        return $reports;
    }

}
