<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    // Module System > Maintenance
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        "B84k.{$_EXTKEY}",
        'system',
        'bkMaintenance',
        'top',
        [
            'BackendModule' => 'index'
        ],
        [
            'access' => 'admin',
            'icon' => "EXT:{$_EXTKEY}/Resources/Public/Icons/module-maintenance.svg",
            'labels' => "LLL:EXT:{$_EXTKEY}/Resources/Private/Language/locallang.xlf"
        ]
    );
}
