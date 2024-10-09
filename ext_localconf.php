<?php
declare(strict_types=1);

/**
 * ext_localconf.php
 *
 * Copyright (C) Leipzig University Library 2024 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if (!defined('TYPO3_MODE')) die('Access denied.');

if ('FE' === TYPO3_MODE) {
    $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
}

// register VuFind authentication service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
		'vufind_auth',
		'auth',
		'Ubl\VufindAuth',
    [
        'title' => 'Vufind Authentication',
        'description' => 'Authenticates users based on authenticated vufind session',
        'subtype' => 'authUserFE,getUserFE,getGroupsFE',
        'available' => true,
        'priority' => 80,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('vufind_auth') . 'Classes/Typo3/Service/Authentication.php',
        'className' => 'Ubl\VufindAuth\Typo3\Service\Authentication'
    ]
);
