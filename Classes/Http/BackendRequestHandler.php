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
use TYPO3\CMS\Backend\Http\RequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class RequestHandler
 * @package B84k\BkMaintenance\Http
 */
class BackendRequestHandler extends AbstractRequestHandler
{
    /**
     * Handles a raw request
     *
     * @param ServerRequestInterface $request
     * @return NULL|ResponseInterface
     * @api
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->bootstrap->initializeBackendUser();

        if (!$GLOBALS['BE_USER']->user || $this->isAdmin() || $this->isUserInMaintenanceGroup()) {
            $requestHandler = GeneralUtility::makeInstance(RequestHandler::class, $this->bootstrap);
            if ($requestHandler->canHandleRequest($request)) {
                return $requestHandler->handleRequest($request);
            }
        }

        $message = isset($this->extConf['pageUnavailable_message']) ? $this->extConf['pageUnavailable_message'] : 'This page is temporarily unavailable.';
        $this->controller->pageUnavailableAndExit($message);
    }

    /**
     * Checks if the request handler can handle the given request.
     *
     * @param ServerRequestInterface $request
     * @return bool TRUE if it can handle the request, otherwise FALSE
     * @api
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        // This request handler does not handle AJAX requests
        if ($request->getAttribute('isAjaxRequest', false)) {
            return false;
        }

        // This request handler does not handle Module requests
        if ($request->getAttribute('isModuleRequest', false)) {
            return false;
        }

        // Handle all other requests
        return TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI);
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request. An integer > 0 means "I want to handle this request" where
     * "100" is default. "0" means "I am a fallback solution".
     *
     * @return int The priority of the request handler
     * @api
     */
    public function getPriority()
    {
        if ($this->isMaintenanceMode()) {
            return 201;
        }
        return 2;
    }
}