<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use Doctrine\DBAL\Exception\InvalidFieldNameException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\Model\CriticalCss;

class DatabaseService
{
    protected const TABLE = 'pages';

    protected static function getQueryBuilder(CriticalCss $criticalCss = null): QueryBuilder
    {
        if ($criticalCss === null) {
            return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        }

        $queryBuilder = self::getQueryBuilder();

        // All translations of a record
        if ($criticalCss->getLanguage() === null) {
            $queryBuilder->where($queryBuilder->expr()->eq('uid', $criticalCss->getUid()));
            if (isset($GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'])) {
                $queryBuilder->orWhere($queryBuilder->expr()->eq($GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'], $criticalCss->getUid()));
            }
        }

        // Default language only
        if ($criticalCss->getLanguage() === 0) {
            $queryBuilder->where($queryBuilder->expr()->eq('uid', $criticalCss->getUid()));
        }

        // Specific language
        if ($criticalCss->getLanguage() && isset($GLOBALS['TCA'][self::TABLE]['ctrl']['languageField'], $GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'])) {
            $queryBuilder->where(
                $queryBuilder->expr()->eq($GLOBALS['TCA'][self::TABLE]['ctrl']['languageField'], $criticalCss->getLanguage()),
                $queryBuilder->expr()->eq($GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'], $criticalCss->getUid())
            );
        }

        return $queryBuilder;
    }

    protected static function log(\Exception $exception): void
    {
        LogService::systemError($exception->getMessage() . ' (' . self::class . ': ' . debug_backtrace()[1]['function'] . ')');
    }

    public static function update(CriticalCss $criticalCss): void
    {
        $allowedFields = ['critical_css_disabled', 'critical_css_status', 'critical_css'];

        try {
            $queryBuilder = self::getQueryBuilder($criticalCss)->update(self::TABLE);

            foreach ($criticalCss->toArray() as $key => $value) {
                if (in_array($key, $allowedFields, true)) {
                    $queryBuilder->set($key, (string)(is_bool($value) ? (int)$value : $value));
                }
            }

            $queryBuilder->execute();
        } catch (InvalidFieldNameException $exception) {
            self::log($exception);
        }
    }

    public static function updateStatus(CriticalCss $criticalCss): void
    {
        try {
            self::getQueryBuilder($criticalCss)->update(self::TABLE)->set('critical_css_status', $criticalCss->getStatus())->execute();
        } catch (InvalidFieldNameException $exception) {
            self::log($exception);
        }
    }

    public static function flushAll(): void
    {
        try {
            self::getQueryBuilder()
                ->update(self::TABLE)
                ->set('critical_css_status', 0)
                ->set('critical_css', '')
                ->execute();
        } catch (InvalidFieldNameException $exception) {
            self::log($exception);
        }
    }

    public static function countStatus(): array
    {
        try {
            $data = [
                CriticalCss::STATUS_EXPIRED => 0,
                CriticalCss::STATUS_PENDING => 0,
                CriticalCss::STATUS_ACTUAL => 0,
                CriticalCss::STATUS_ERROR => 0,
            ];

            $queryBuilder = self::getQueryBuilder();

            $results = $queryBuilder->select('critical_css_status')
                ->addSelectLiteral($queryBuilder->expr()->count('uid', 'count'))
                ->from(self::TABLE)
                ->where($queryBuilder->expr()->notIn('doktype', [
                    PageRepository::DOKTYPE_LINK,
                    PageRepository::DOKTYPE_SHORTCUT,
                    PageRepository::DOKTYPE_BE_USER_SECTION,
                    PageRepository::DOKTYPE_MOUNTPOINT,
                    PageRepository::DOKTYPE_SPACER,
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_RECYCLER
                ]))
                ->orderBy('critical_css_status')
                ->groupBy('critical_css_status')
                ->execute()
                ->fetchAllAssociative();

            foreach ($results as $result) {
                $data[$result['critical_css_status']] = (int)$result['count'];
            }

            return $data;
        } catch (InvalidFieldNameException $exception) {
            self::log($exception);

            return [];
        }
    }
}
