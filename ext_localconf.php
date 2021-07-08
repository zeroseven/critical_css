<?php

defined('TYPO3_MODE') || die('🐰');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->addCriticalCss';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \Zeroseven\CriticalCss\Hooks\ClearCacheCommandHook::class . '->clearCachePostProc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = \Zeroseven\CriticalCss\Hooks\ClearCacheToolbarItemHook::class;
