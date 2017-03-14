<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

const COMPOSER_AUTOLOAD_PATH = PATH_site . 'Packages/Libraries/autoload.php';

if (!file_exists(COMPOSER_AUTOLOAD_PATH) && 'BE' === TYPO3_MODE) {
    $msg = t3lib_div::makeInstance('t3lib_FlashMessage', 'Make sure to install zendframework-stdlib via composer under "Packages/Libraries"', 'Composer autoload not found', t3lib_FlashMessage::WARNING);
    return t3lib_FlashMessageQueue::addMessage($msg);
} else {
    // require composers autoloader
    require_once COMPOSER_AUTOLOAD_PATH;
}

if ('FE' === TYPO3_MODE) {
    $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
}

// add the Vufind Authentication Service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY, 'auth', 'LeipzigUniversityLibrary\VufindAuth',
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
        'className' => 'LeipzigUniversityLibrary\VufindAuth\Typo3\Service\Authentication',
    )
);
