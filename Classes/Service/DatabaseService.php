<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\Model\Styles;

class DatabaseService
{
    protected const TABLE = 'pages';

    protected static function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
    }

    public static function update(Styles $criticalCss): void
    {
        $queryBuilder = self::getQueryBuilder();
        $queryBuilder->update(self::TABLE)->where($queryBuilder->expr()->eq('uid', $criticalCss->getUid()));

        $allowedFields = ['critical_css_disabled', 'critical_css_status', 'critical_css'];

        foreach ($criticalCss->toArray() as $key => $value) {
            if (in_array($key, $allowedFields, true)) {
                $queryBuilder->set($key, (string)(is_bool($value) ? (int)$value : $value));
            }
        }

        $queryBuilder->execute();
    }

    public static function updateStatus(Styles $criticalCss): void
    {
        $queryBuilder = self::getQueryBuilder();

        $queryBuilder->update(self::TABLE)
            ->set('critical_css_status', $criticalCss->getStatus())
            ->where($queryBuilder->expr()->eq('uid', $criticalCss->getUid()))
            ->execute();
    }

    public static function flushAll(): void
    {
        self::getQueryBuilder()
            ->update(self::TABLE)
            ->set('critical_css_status', 0)
            ->set('critical_css', '')
            ->execute();
    }
}
