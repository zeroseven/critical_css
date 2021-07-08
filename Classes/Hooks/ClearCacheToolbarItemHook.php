<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ClearCacheToolbarItemHook implements ClearCacheActionsHookInterface
{
    public function manipulateCacheActions(&$cacheActions, &$optionValues): void
    {
        $cacheActions[] = [
            'id' => 'critical_css',
            'title' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_be.xlf:flushCache.title',
            'description' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_be.xlf:flushCache.description',
            'href' => (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('tce_db', ['cacheCmd' => 'critical_css']),
            'iconIdentifier' => 'apps-toolbar-menu-cache',
        ];

        $optionValues[] = 'critical_css';
    }
}
