<?php

defined('TYPO3_MODE') || die('🐰');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->postProcess';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] = \Zeroseven\CriticalCss\Hooks\PageRendererHook::class . '->preProcess';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = \Zeroseven\CriticalCss\Hooks\DataHandlerHook::class . '->clearCachePostProc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][] = \Zeroseven\CriticalCss\Hooks\ClearCacheToolbarItemHook::class;
