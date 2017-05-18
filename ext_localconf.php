<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] = 'EXT:bk_maintenance/Classes/UserFunctions.php:UserFunctions->preprocessRequest';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Http\\RequestHandler'] = array(
    'className' => 'B84k\\BkMaintenance\\Xclass\\RequestHandler'
);
