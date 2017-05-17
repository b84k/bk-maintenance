<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'System>Maintenance',
    'description' => 'The Maintenance Tool mounted as the module System>Maintenance in TYPO3.',
    'category' => 'module',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Benjamin Kurz',
    'author_email' => 'benjamin.kurz@liebenzell.org',
    'author_company' => 'Liebenzeller Mission gGmbH',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '7.6.0-8.7.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
