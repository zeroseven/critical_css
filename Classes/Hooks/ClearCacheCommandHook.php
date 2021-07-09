<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
        // Build query
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages')
            ->update('pages')
            ->set('critical_css_status', 0);

        // Limit to affected page
        if ($pageUid = $this->getAffectedPage($params)) {
            $queryBuilder->where($queryBuilder->expr()->eq('uid', $pageUid));
        }

        // Ciao!
        $queryBuilder->execute();
    }
}
