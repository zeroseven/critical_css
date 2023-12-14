<?php

defined('TYPO3') || die('💐');

call_user_func(static function (string $table) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, [
        'critical_css_disabled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css_disabled',
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
            'label' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css_status',
            'displayCond' => 'FIELD:critical_css_disabled:REQ:false',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Expired', \Zeroseven\CriticalCss\Model\CriticalCss::STATUS_EXPIRED, 'overlay-endtime'],
                    ['Pending', \Zeroseven\CriticalCss\Model\CriticalCss::STATUS_PENDING, 'overlay-scheduled'],
                    ['Actual', \Zeroseven\CriticalCss\Model\CriticalCss::STATUS_ACTUAL, 'overlay-approved'],
                    ['Error', \Zeroseven\CriticalCss\Model\CriticalCss::STATUS_ERROR, 'overlay-warning']
                ],
                'readOnly' => true,
                'default' => '0'
            ]
        ],
        'critical_css' => [
            'exclude' => true,
            'label' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_db.xlf:pages.critical_css',
            'displayCond' => 'FIELD:critical_css_disabled:REQ:false',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
                'default' => ''
            ]
        ]
    ]);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette($table, 'critical_css', 'critical_css_disabled, --linebreak--, critical_css_status, --linebreak--, critical_css');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($table, '--palette--;Critical css;critical_css', '', 'after:tsconfig_includes');
}, 'pages');
