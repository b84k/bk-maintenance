<?php

namespace B84k\BkMaintenance\Xclass;

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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\FrontendEditing\FrontendEditingController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\View\AdminPanelView;

/**
 * This is the main entry point of the TypoScript driven standard front-end
 *
 * Basically put, this is the script which all requests for TYPO3 delivered pages goes to in the
 * frontend (the website). The script instantiates a $TSFE object, includes libraries and does a little logic here
 * and there in order to instantiate the right classes to create the webpage.
 * Previously, this was called index_ts.php and also included the logic for the lightweight "eID" concept,
 * which is now handled in a separate request handler (EidRequestHandler).
 */
class FrontendRequestHandler extends \TYPO3\CMS\Frontend\Http\RequestHandler
{
    /**
     * Handles a frontend request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return NULL|\Psr\Http\Message\ResponseInterface
     */
    public function handleRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $version = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());

        $response = null;
        $this->request = $request;
        $this->initializeTimeTracker();

        // Hook to preprocess the current request:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
                $hookParameters = [];
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
            }
            unset($hookFunction);
            unset($hookParameters);
        }

        $this->initializeController();

        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_force']
            && !GeneralUtility::cmpIP(
                GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])
        ) {
            // Initializing a possible logged-in Backend User
            /** @var $GLOBALS ['BE_USER'] \TYPO3\CMS\Backend\FrontendBackendUserAuthentication */
            $GLOBALS['BE_USER'] = $this->controller->initializeBackendUser();
            if (!$this->controller->isBackendUserLoggedIn()) {
                $extConfVars = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['bk_maintenance']);
                $message = isset($extConfVars['maintenance_message']) ? $extConfVars['maintenance_message'] : 'This page is temporarily unavailable.';
                $this->controller->pageUnavailableAndExit($message);
            }
        }

        $this->controller->connectToDB();
        $this->controller->sendRedirect();

        // Output compression
        // Remove any output produced until now
        $this->bootstrap->endOutputBufferingAndCleanPreviousOutput();
        $this->initializeOutputCompression();

        if ($version['version_main'] == 8) {
            $this->bootstrap->loadBaseTca();
        }

        // Initializing the Frontend User
        $this->timeTracker->push('Front End user initialized', '');
        $this->controller->initFEuser();
        $this->timeTracker->pull();

        // Initializing a possible logged-in Backend User
        /** @var $GLOBALS ['BE_USER'] \TYPO3\CMS\Backend\FrontendBackendUserAuthentication */
        $GLOBALS['BE_USER'] = $this->controller->initializeBackendUser();

        // Process the ID, type and other parameters.
        // After this point we have an array, $page in TSFE, which is the page-record
        // of the current page, $id.
        $this->timeTracker->push('Process ID', '');
        // Initialize admin panel since simulation settings are required here:
        if ($this->controller->isBackendUserLoggedIn()) {
            $GLOBALS['BE_USER']->initializeAdminPanel();

            if ($version['version_main'] == 8) {
                $this->bootstrap
                    ->initializeBackendRouter()
                    ->loadExtTables();
            } elseif ($version['version_main'] == 7) {
                $this->bootstrap
                    ->initializeBackendRouter()
                    ->loadExtensionTables(true);
            }
        } else {
            if ($version['version_main'] == 7) {
                $this->bootstrap->loadCachedTca();
            }
        }

        $this->controller->checkAlternativeIdMethods();
        $this->controller->clear_preview();
        $this->controller->determineId();

        // Now, if there is a backend user logged in and he has NO access to this page,
        // then re-evaluate the id shown! _GP('ADMCMD_noBeUser') is placed here because
        // \TYPO3\CMS\Version\Hook\PreviewHook might need to know if a backend user is logged in.
        if (
            $this->controller->isBackendUserLoggedIn()
            && (!$GLOBALS['BE_USER']->extPageReadAccess($this->controller->page) || GeneralUtility::_GP('ADMCMD_noBeUser'))
        ) {
            // Remove user
            unset($GLOBALS['BE_USER']);
            $this->controller->beUserLogin = false;
            // Re-evaluate the page-id.
            $this->controller->checkAlternativeIdMethods();
            $this->controller->clear_preview();
            $this->controller->determineId();
        }

        $this->controller->makeCacheHash();
        $this->timeTracker->pull();

        // Admin Panel & Frontend editing
        if ($this->controller->isBackendUserLoggedIn()) {
            $GLOBALS['BE_USER']->initializeFrontendEdit();
            if ($GLOBALS['BE_USER']->adminPanel instanceof AdminPanelView) {
                $this->bootstrap->initializeLanguageObject();
            }
            if ($GLOBALS['BE_USER']->frontendEdit instanceof FrontendEditingController) {
                $GLOBALS['BE_USER']->frontendEdit->initConfigOptions();
            }
        }

        // Starts the template
        $this->timeTracker->push('Start Template', '');
        $this->controller->initTemplate();
        $this->timeTracker->pull();
        // Get from cache
        $this->timeTracker->push('Get Page from cache', '');
        $this->controller->getFromCache();
        $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $this->controller->getConfigArray();
        // Setting language and locale
        $this->timeTracker->push('Setting language and locale', '');
        $this->controller->settingLanguage();
        $this->controller->settingLocale();
        $this->timeTracker->pull();

        // Convert POST data to utf-8 for internal processing if metaCharset is different
        $this->controller->convPOSTCharset();

        $this->controller->initializeRedirectUrlHandlers();

        $this->controller->handleDataSubmission();

        // Check for shortcut page and redirect
        $this->controller->checkPageForShortcutRedirect();
        $this->controller->checkPageForMountpointRedirect();

        // Generate page
        $this->controller->setUrlIdToken();
        $this->timeTracker->push('Page generation', '');
        if ($this->controller->isGeneratePage()) {
            $this->controller->generatePage_preProcessing();
            $temp_theScript = $this->controller->generatePage_whichScript();
            if ($temp_theScript) {
                include $temp_theScript;
            } else {
                if ($version['version_main'] == 8) {
                    $this->controller->preparePageContentGeneration();
                } elseif ($version['version_main'] == 7) {
                    PageGenerator::pagegenInit();
                    // Global content object
                    $this->controller->newCObj();
                }

                // Content generation
                if (!$this->controller->isINTincScript()) {
                    PageGenerator::renderContent();
                    $this->controller->setAbsRefPrefix();
                }
            }
            $this->controller->generatePage_postProcessing();
        } elseif ($this->controller->isINTincScript()) {
            if ($version['version_main'] == 8) {
                $this->controller->preparePageContentGeneration();
            } elseif ($version['version_main'] == 7) {
                PageGenerator::pagegenInit();
                // Global content object
                $this->controller->newCObj();
            }
        }
        $this->controller->releaseLocks();
        $this->timeTracker->pull();

        // Render non-cached parts
        if ($this->controller->isINTincScript()) {
            $this->timeTracker->push('Non-cached objects', '');
            $this->controller->INTincScript();
            $this->timeTracker->pull();
        }

        // Output content
        $sendTSFEContent = false;
        if ($this->controller->isOutputting()) {
            $this->timeTracker->push('Print Content', '');
            $this->controller->processOutput();
            $sendTSFEContent = true;
            $this->timeTracker->pull();
        }
        // Store session data for fe_users
        $this->controller->storeSessionData();
        // Statistics
        $GLOBALS['TYPO3_MISC']['microtime_end'] = microtime(true);
        if ($this->controller->isOutputting()) {
            if (isset($this->controller->config['config']['debug'])) {
                $debugParseTime = (bool)$this->controller->config['config']['debug'];
            } else {
                $debugParseTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
            }
            if ($debugParseTime) {
                if ($version['version_main'] == 8) {
                    $this->controller->content .= LF . '<!-- Parsetime: ' . $this->timeTracker->getParseTime() . 'ms -->';
                } elseif ($version['version_main'] == 7) {
                    $this->controller->content .= LF . '<!-- Parsetime: ' . $this->controller->scriptParseTime . 'ms -->';
                }
            }
        }
        $this->controller->redirectToExternalUrl();
        // Preview info
        $this->controller->previewInfo();
        // Hook for end-of-frontend
        $this->controller->hook_eofe();
        // Finish timetracking
        $this->timeTracker->pull();

        // Admin panel
        if ($this->controller->isBackendUserLoggedIn() && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            if ($GLOBALS['BE_USER']->isAdminPanelVisible()) {
                $this->controller->content = str_ireplace('</body>', $GLOBALS['BE_USER']->displayAdminPanel() . '</body>', $this->controller->content);
            }
        }

        if ($sendTSFEContent) {
            /** @var \TYPO3\CMS\Core\Http\Response $response */
            $response = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\Response::class);
            $response->getBody()->write($this->controller->content);
        }
        // Debugging Output
        if (isset($GLOBALS['error']) && is_object($GLOBALS['error']) && @is_callable([$GLOBALS['error'], 'debugOutput'])) {
            $GLOBALS['error']->debugOutput();
        }
        if (TYPO3_DLOG) {
            GeneralUtility::devLog('END of FRONTEND session', 'cms', 0, ['_FLUSH' => true]);
        }
        return $response;
    }
}