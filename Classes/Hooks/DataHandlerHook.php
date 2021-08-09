<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\CriticalCss\Model\Page;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\SettingsService;

class DataHandlerHook
{
    protected function getAffectedPage(array $params): int
    {
        if ($params['uid_page'] ?? null) {
            return (int)$params['uid_page'];
        }

        if (MathUtility::canBeInterpretedAsInteger($params['cacheCmd'] ?? null)) {
            return (int)$params['cacheCmd'];
        }

        return 0;
    }

    public function clearCachePostProc(array &$params): void
    {
        $cacheCmd = $params['cacheCmd'];

        // Do nothing on some conditions
        if (
            ($params['tags']['ignore_critical_css'] ?? false) // Ignore critical css styles from flushing cache ...
            || ($cacheCmd === 'pages' || $cacheCmd === 'lowlevel')  // It's the "flush frontend cache" command ...
            || SettingsService::isDisabled() // Service is disabled ...
        ) {
            return;
        }

        // A specific page should be flushed. Maybe something was changed in the content.
        if ($pageUid = $this->getAffectedPage($params)) {
            DatabaseService::updateStatus(Page::makeInstance()->setStatus(0)->setUid($pageUid));
        }

        // Reset all critical styles.
        if ($cacheCmd === ClearCacheToolbarItemHook::CACHE_CMD || $cacheCmd === 'all') {
            DatabaseService::flushAll();
        }
    }
}
