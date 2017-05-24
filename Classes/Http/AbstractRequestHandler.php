<?php

namespace B84k\BkMaintenance\Http;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class AbstractRequestHandler
 * @package B84k\BkMaintenance\Http
 */
abstract class AbstractRequestHandler implements RequestHandlerInterface
{
    const EXT_KEY = 'bk_maintenance';
    const EXT_GROUP_MAINTENANCE = 'maintenance';

    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var array
     */
    protected $extConf;

    /**
     * Constructor handing over the bootstrap and the original request
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->init($bootstrap);
        $this->initExtConf();
    }

    /**
     * Initialization
     * @param $bootstrap
     */
    protected function init($bootstrap)
    {
        if (!$this->bootstrap instanceof Bootstrap) {
            $this->bootstrap = $bootstrap;
        }
    }

    /**
     * Initialization of extension configuration
     */
    private function initExtConf()
    {
        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::EXT_KEY]);
    }

    /**
     * Handles a raw request
     *
     * @param ServerRequestInterface $request
     * @return NULL|ResponseInterface
     * @api
     */
    public abstract function handleRequest(ServerRequestInterface $request);

    /**
     * Checks if the request handler can handle the given request.
     *
     * @param ServerRequestInterface $request
     * @return bool TRUE if it can handle the request, otherwise FALSE
     * @api
     */
    public abstract function canHandleRequest(ServerRequestInterface $request);

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request. An integer > 0 means "I want to handle this request" where
     * "100" is default. "0" means "I am a fallback solution".
     *
     * @return int The priority of the request handler
     * @api
     */
    public abstract function getPriority();

    /**
     * Check if the maintenance mode is active.
     * @return bool
     */
    protected function isMaintenanceMode()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_force'];
    }

    /**
     * Check if the currently logged in user is an admin.
     * @return bool
     */
    protected function isAdmin()
    {
        if ($GLOBALS['BE_USER']->user) {
            return $GLOBALS['BE_USER']->isAdmin();
        }
        return false;
    }

    /**
     * Check if the currently logged in user is in the maintenance group.
     * @return bool
     */
    protected function isUserInMaintenanceGroup()
    {
        if ($GLOBALS['BE_USER']->user) {
            $userGroups = explode(',', $GLOBALS['BE_USER']->user['usergroup']);
            foreach ($userGroups as $key => $value) {
                if (!empty($value)) {
                    $userGroup = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('title', 'be_groups', "uid={$value}");
                    if (strtolower($userGroup['title']) === $this->extConf['maintenance_group']) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     *
     */
    protected function handleMaintenancePage() {
        $handling = $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'];
        $header = $header ?: $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader'];
        $message = isset($this->extConf['pageUnavailable_message']) ? $this->extConf['pageUnavailable_message'] : 'This page is temporarily unavailable.';

        if ($header) {
            $headerArr = preg_split('/\\r|\\n/', $header, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($headerArr as $header) {
                header($header);
            }
        }

        if (GeneralUtility::isFirstPartOfStr($handling, 'READFILE:')) {
            $readFile = GeneralUtility::getFileAbsFileName(trim(substr($handling, 9)));
            if (@is_file($readFile)) {
                echo str_replace(
                    [
                        '###CURRENT_URL###',
                        '###REASON###'
                    ],
                    [
                        GeneralUtility::getIndpEnv('REQUEST_URI'),
                        htmlspecialchars($message)
                    ],
                    GeneralUtility::getUrl($readFile)
                );
            } else {
                throw new \RuntimeException('Configuration Error: 404 page "' . $readFile . '" could not be found.', 1294587214);
            }
        }

        die;
    }
}
