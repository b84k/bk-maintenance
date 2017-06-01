<?php

namespace B84k\BkMaintenance\Controller;

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

use B84k\BkMaintenance\Domain\Model\Maintenance;
use B84k\BkMaintenance\Domain\Model\Template;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Core\Configuration\ConfigurationManager as ConfigurationFileManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class BackendModuleController
 * @package B84k\BkMaintenance\Controller
 * @author Benjamin Kurz <benjamin.kurz@liebenzell.org>
 */
class BackendModuleController extends ActionController
{
    const EXT_KEY = 'bk_maintenance';
    const MAINTENANCE_PREFIX = '!! MAINTENANCE !! ';
    const MAINTENANCE_GROUP = 'maintenance_group';
    const MAINTENANCE_TEMPLATES = 'maintenance_templates';
    const MAINTENANCE_TEMPLATE = 'maintenance_template';
    const MAINTENANCE_MESSAGE = 'maintenance_message';
    const MAINTENANCE_DISABLE_SCHEDULER_TASKS = 'maintenance_disableSchedulerTasks';
    const PAGE_UNAVAILABLE_FORCE = 'pageUnavailable_force';
    const PAGE_UNAVAILABLE_HANDLING = 'pageUnavailable_handling';

    /** @var ConfigurationFileManager $configurationFileManager */
    protected $configurationFileManager;

    /**
     * @var array
     */
    protected $confVars = array();

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @var BackendUserRepository
     */
    protected $backendUserRepository;

    /**
     * @param BackendUserGroupRepository $backendUserGroupRepository
     */
    public function injectBackendUserGroupRepository(BackendUserGroupRepository $backendUserGroupRepository)
    {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
    }

    /**
     * @param BackendUserRepository $backendUserRepository
     */
    public function injectBackendUserRepository(BackendUserRepository $backendUserRepository)
    {
        $this->backendUserRepository = $backendUserRepository;
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        // Nothing to do yet
    }

    /**
     * Initializes the controller before invoking an action method.
     */
    protected function initializeAction()
    {
        $this->configurationFileManager = GeneralUtility::makeInstance(ConfigurationFileManager::class);
    }

    /**
     *
     */
    protected function indexAction()
    {
        $this->initConfVars();
        $templates = $this->initTemplateModels();
        $maintenance = $this->initMaintenanceModel();

        $this->view->assign('templates', $templates);
        $this->view->assign('maintenance', $maintenance);
        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('returnUrl', rawurlencode(BackendUtility::getModuleUrl('system_BkMaintenanceBkmaintenance')));
    }

    /**
     * @param \B84k\BkMaintenance\Domain\Model\Maintenance $newMaintenance
     */
    public function saveAction(Maintenance $newMaintenance)
    {
        $extensionConfVars = [
            self::MAINTENANCE_GROUP => trim($newMaintenance->getGroup()),
            self::MAINTENANCE_TEMPLATE => $newMaintenance->getTemplate(),
            self::MAINTENANCE_MESSAGE => $newMaintenance->getMessage(),
            self::MAINTENANCE_DISABLE_SCHEDULER_TASKS => $newMaintenance->getSchedulerTasksDisabled(),
        ];

        // Write changes to local configuration file.
        $configurationPathValuePairs = [
            'FE/' . self::PAGE_UNAVAILABLE_FORCE => $newMaintenance->getMaintenanceModeEnabled(),
            'FE/' . self::PAGE_UNAVAILABLE_HANDLING => 'READFILE:typo3conf/ext/bk_maintenance/Resources/Private/Templates/Template' . $newMaintenance->getTemplate() . '.html',
            'EXT/extConf/' . self::EXT_KEY => serialize($extensionConfVars)
        ];

        if ($newMaintenance->getMaintenanceModeEnabled()) {
            if (strpos($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],self::MAINTENANCE_PREFIX) === false) {
                $configurationPathValuePairs['SYS/sitename'] = self::MAINTENANCE_PREFIX . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            }
        } else {
            $start = strpos($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],self::MAINTENANCE_PREFIX) + strlen(self::MAINTENANCE_PREFIX);
            $configurationPathValuePairs['SYS/sitename'] = substr($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], $start);
        }

        $this->configurationFileManager->setLocalConfigurationValuesByPathValuePairs($configurationPathValuePairs);

        // Redirect to index page
        $this->redirect('index');
    }

    /**
     * Initialize the configuration values which are stored in the LocalConfiguration.php
     */
    protected function initConfVars()
    {
        $defaultTemplates = [
            [
                'name' => 'Template 1',
                'path' => 'typo3conf/ext/bk_maintenance/Resources/Private/Templates/Template1.html'
            ]
        ];

        $extensionConfVars = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['bk_maintenance']);
        $this->confVars[self::MAINTENANCE_GROUP] = isset($extensionConfVars[self::MAINTENANCE_GROUP]) ? $extensionConfVars[self::MAINTENANCE_GROUP] : 'maintenance';
        $this->confVars[self::MAINTENANCE_TEMPLATES] = isset($extensionConfVars[self::MAINTENANCE_TEMPLATES]) ? $extensionConfVars[self::MAINTENANCE_TEMPLATES] : $defaultTemplates;
        $this->confVars[self::MAINTENANCE_TEMPLATE] = isset($extensionConfVars[self::MAINTENANCE_TEMPLATE]) ? $extensionConfVars[self::MAINTENANCE_TEMPLATE] : 0;
        $this->confVars[self::MAINTENANCE_MESSAGE] = isset($extensionConfVars[self::MAINTENANCE_MESSAGE]) ? $extensionConfVars[self::MAINTENANCE_MESSAGE] : '';
        $this->confVars[self::MAINTENANCE_DISABLE_SCHEDULER_TASKS] = isset($extensionConfVars[self::MAINTENANCE_DISABLE_SCHEDULER_TASKS]) ? $extensionConfVars[self::MAINTENANCE_DISABLE_SCHEDULER_TASKS] : false;
        $this->confVars[self::PAGE_UNAVAILABLE_FORCE] = $GLOBALS['TYPO3_CONF_VARS']['FE'][self::PAGE_UNAVAILABLE_FORCE];
    }

    /**
     * Initialize template models
     *
     * @return array
     */
    protected function initTemplateModels()
    {
        $templates = array();
        if (is_array($this->confVars[self::MAINTENANCE_TEMPLATES])) {
            foreach ($this->confVars[self::MAINTENANCE_TEMPLATES] as $key => $value) {
                /** @var Template $template */
                $template = $this->objectManager->get(Template::class);
                $template->_setProperty('uid', $key);
                $template->setName($value['name']);
                $template->setPath($value['path']);
                $templates[] = $template;
            }
        }
        return $templates;
    }

    /**
     * @return Maintenance
     */
    protected function initMaintenanceModel()
    {
        /** @var Maintenance $maintenance */
        $maintenance = $this->objectManager->get(Maintenance::class);
        $maintenance->setGroup($this->confVars[self::MAINTENANCE_GROUP]);
        $maintenance->setTemplate($this->confVars[self::MAINTENANCE_TEMPLATE]);
        $maintenance->setMessage($this->confVars[self::MAINTENANCE_MESSAGE]);
        $maintenance->setSchedulerTasksDisabled($this->confVars[self::MAINTENANCE_DISABLE_SCHEDULER_TASKS]);
        $maintenance->setMaintenanceModeEnabled($this->confVars[self::PAGE_UNAVAILABLE_FORCE]);

        $backendUserGroups = $this->backendUserGroupRepository->findAll();
        foreach ($backendUserGroups as $key => $backendUserGroup) {
            if ($this->confVars[self::MAINTENANCE_GROUP] === strtolower($backendUserGroup->getTitle())) {
                $maintenance->setBackendUserGroup($backendUserGroup);

                $uid = $backendUserGroup->getUid();
                $query = $this->backendUserRepository->createQuery();
                $query->matching(
                    $query->logicalOr(
                        $query->like('usergroup', "%{$uid}%"),
                        $query->equals('admin', 1)
                    )
                );

                $backendUsers = $query->execute();
                foreach ($backendUsers as $key => $backendUser) {
                    $maintenance->addBackendUser($backendUser);
                }
            }
        }
        return $maintenance;
    }
}
