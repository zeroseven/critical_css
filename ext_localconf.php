<?php

defined('TYPO3_MODE') || die('🐰');

call_user_func(static function (string $_EXTKEY) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][$_EXTKEY] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->postProcess';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][$_EXTKEY] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->preProcess';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$_EXTKEY] = \Zeroseven\CriticalCss\Hooks\DataHandlerHook::class . '->clearCachePostProc';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][$_EXTKEY] = \Zeroseven\CriticalCss\Hooks\ClearCacheToolbarItemHook::class;
}, \Zeroseven\CriticalCss\Service\SettingsService::EXTENSION_KEY);

