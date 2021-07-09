<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\CriticalCss\Model\CriticalCss;
use Zeroseven\CriticalCss\Service\DatabaseService;

class ClearCacheCommandHook
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
        // It's the "flush frontend cache" command. Ignore!
        if ($params['cacheCmd'] === 'pages') {
            return;
        }

        // A specific page should be flushed – ok!
        if ($pageUid = $this->getAffectedPage($params)) {
            DatabaseService::updateStatus(CriticalCss::makeInstance()->setStatus(0)->setUid($pageUid));
        }

        // Reset all critical styles
        if ($params['cacheCmd'] === ClearCacheToolbarItemHook::CACHE_CMD || $params['cacheCmd'] === 'all') {
            DatabaseService::flushAll();
        }
    }
}
