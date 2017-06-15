<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if ('FE' === TYPO3_MODE) {
    $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
}

// add the Vufind Authentication Service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'Ubl\VufindAuth',
    array(
        'title' => 'Vufind Authentication',
        'description' => 'Authenticates users based on authenticated vufind session',
        'subtype' => 'authUserFE,getUserFE,getGroupsFE',
        'available' => true,
        'priority' => 80,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Typo3/Service/Authentication.php',
        'className' => 'Ubl\VufindAuth\Typo3\Service\Authentication',
    )
);
