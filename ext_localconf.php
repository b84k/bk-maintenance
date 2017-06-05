<?php
defined('TYPO3_MODE') or die();

if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->registerRequestHandlerImplementation(
        \B84k\BkMaintenance\Http\BackendRequestHandler::class
    );
    \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->registerRequestHandlerImplementation(
        \B84k\BkMaintenance\Http\FrontendRequestHandler::class
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Http\\RequestHandler'] = array(
        'className' => 'B84k\\BkMaintenance\\Xclass\\FrontendRequestHandler'
    );
}
