<?php

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

namespace B84k\BkMaintenance\Domain\Model;

use TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class Maintenance
 * @package B84k\BkMaintenance\Domain\Model
 * @author Benjamin Kurz <benjamin.kurz@liebenzell.org>
 */
class Maintenance extends AbstractEntity
{
    /**
     * @var string
     */
    protected $group;

    /**
     * @var int
     */
    protected $template;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var BackendUserGroup
     * @lazy
     */
    protected $backendUserGroup;

    /**
     * @var ObjectStorage
     */
    protected $backendUsers;

    /**
     * @var bool
     */
    protected $schedulerTasksDisabled;

    /**
     * @var bool
     */
    protected $maintenanceModeEnabled;

    /**
     * Maintenance constructor.
     */
    public function __construct()
    {
        $this->backendUsers = new ObjectStorage();
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return int
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get backend user group.
     *
     * @return BackendUserGroup
     */
    public function getBackendUserGroup()
    {
        return $this->backendUserGroup;
    }

    /**
     * Set backend user group
     *
     * @param BackendUserGroup $backendUserGroup
     */
    public function setBackendUserGroup($backendUserGroup)
    {
        $this->backendUserGroup = $backendUserGroup;
    }

    /**
     * @return ObjectStorage
     */
    public function getBackendUsers()
    {
        return $this->backendUsers;
    }

    /**
     * @param $backendUsers
     */
    public function setBackendUsers($backendUsers)
    {
        $this->backendUsers = $backendUsers;
    }

    /**
     * @param $backendUser
     */
    public function addBackendUser($backendUser)
    {
        $this->backendUsers->attach($backendUser);
    }

    /**
     * @return bool
     */
    public function getSchedulerTasksDisabled()
    {
        return $this->schedulerTasksDisabled;
    }

    /**
     * @param $schedulerTasksDisabled
     */
    public function setSchedulerTasksDisabled($schedulerTasksDisabled)
    {
        $this->schedulerTasksDisabled = $schedulerTasksDisabled;
    }

    /**
     * @return bool
     */
    public function getMaintenanceModeEnabled()
    {
        return $this->maintenanceModeEnabled;
    }

    /**
     * @param $maintenanceModeEnabled
     */
    public function setMaintenanceModeEnabled($maintenanceModeEnabled)
    {
        $this->maintenanceModeEnabled = $maintenanceModeEnabled;
    }
}
