<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'system',
        'bkMaintenance',
        '',
        '',
        [
            'routeTarget' => \B84K\BkMaintenance\Controller\BackendModuleController::class . '::index',
            'access' => 'admin',
            'name' => 'system_bkMaintenance',
            'icon' => 'EXT:bk_maintenance/Resources/Public/Icons/module-maintenance.svg',
            'labels' => 'LLL:EXT:bk_maintenance/Resources/Private/Language/BackendModule.xlf'
        ]
    );
}
