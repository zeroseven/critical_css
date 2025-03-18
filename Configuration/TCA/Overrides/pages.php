<?php

defined('TYPO3') || die('💐');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'critical_css_disabled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css_disabled',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                        'invertStateDisplay' => true,
                    ],
                ],
                'default' => '0'
            ]
        ],
        'critical_css_status' => [
            'exclude' => true,
            'label' => 'LLL:EXT:critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css_status',
            'displayCond' => 'FIELD:critical_css_disabled:REQ:false',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'Expired',
                        'value' => \Zeroseven\CriticalCss\Model\Page::STATUS_EXPIRED,
                        'icon' => 'overlay-endtime'
                    ],
                    [
                        'label' => 'Pending',
                        'value' => \Zeroseven\CriticalCss\Model\Page::STATUS_PENDING,
                        'icon' => 'overlay-scheduled'
                    ],
                    [
                        'label' => 'Actual',
                        'value' => \Zeroseven\CriticalCss\Model\Page::STATUS_ACTUAL,
                        'icon' => 'overlay-approved'
                    ],
                    [
                        'label' => 'Error',
                        'value' => \Zeroseven\CriticalCss\Model\Page::STATUS_ERROR,
                        'icon' => 'overlay-warning'
                    ]
                ],
                'readOnly' => true,
                'default' => '0'
            ]
        ],
        'critical_css_inline' => [
            'exclude' => true,
            'label' => 'LLL:EXT:critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css_inline',
            'displayCond' => 'FIELD:critical_css_disabled:REQ:false',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
                'default' => ''
            ]
        ],
        'critical_css_linked' => [
            'exclude' => true,
            'label' => 'LLL:EXT:critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css_linked',
            'displayCond' => 'FIELD:critical_css_disabled:REQ:false',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
                'default' => ''
            ]
        ]
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette($table, 'critical_css', 'critical_css_disabled, --linebreak--, critical_css_status, --linebreak--, critical_css_inline, --linebreak--, critical_css_inline, --linebreak--, critical_css_linked');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, '--palette--;Critical css;critical_css', '', 'after:tsconfig_includes');
}, 'pages');
