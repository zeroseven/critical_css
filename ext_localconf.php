<?php

defined('TYPO3_MODE') || die('🐰');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][\Zeroseven\CriticalCss\Service\SettingsService::EXTENSION_KEY] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->postProcess';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][\Zeroseven\CriticalCss\Service\SettingsService::EXTENSION_KEY] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->preProcess';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][\Zeroseven\CriticalCss\Service\SettingsService::EXTENSION_KEY] = \Zeroseven\CriticalCss\Hooks\DataHandlerHook::class . '->clearCachePostProc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][\Zeroseven\CriticalCss\Service\SettingsService::EXTENSION_KEY] = \Zeroseven\CriticalCss\Hooks\ClearCacheToolbarItemHook::class;
