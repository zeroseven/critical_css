<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\Model\Page;

class DatabaseService
{
    protected const TABLE = 'pages';

    protected static function getQueryBuilder(?Page $page = null): QueryBuilder
    {
        if ($page === null) {
            return GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable(self::TABLE);
        }

        $queryBuilder = self::getQueryBuilder();
        $transOrigPointerField = $GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA'][self::TABLE]['ctrl']['languageField'];

        // All translations of a record
        if ($page->getLanguage() === null) {
            $queryBuilder->where($queryBuilder->expr()->eq('uid', $page->getUid()));
            $queryBuilder->orWhere($queryBuilder->expr()->eq($transOrigPointerField, $page->getUid()));
        }

        // Default language only
        if ($page->getLanguage() === 0) {
            $queryBuilder->where($queryBuilder->expr()->eq('uid', $page->getUid()));
        }

        // Specific language
        if ($page->getLanguage()) {
            $queryBuilder->where(
                $queryBuilder->expr()->eq($languageField, $page->getLanguage()),
                $queryBuilder->expr()->eq($transOrigPointerField, $page->getUid())
            );
        }

        return $queryBuilder;
    }

    protected static function log(\Exception $exception): void
    {
        LogService::systemError($exception->getMessage() . ' (' . self::class . ': ' . debug_backtrace()[1]['function'] . ')');
    }

    public static function update(Page $page): void
    {
        $allowedFields = ['critical_css_disabled', 'critical_css_status', 'critical_css'];

        $queryBuilder = self::getQueryBuilder($page)->update(self::TABLE);

        foreach ($page->toArray() as $key => $value) {
            if (in_array($key, $allowedFields, true)) {
                $queryBuilder->set($key, (string)(is_bool($value) ? (int)$value : $value));
            }
        }

        $queryBuilder->executeStatement();
    }

    public static function updateStatus(Page $page): void
    {
        self::getQueryBuilder($page)->update(self::TABLE)->set('critical_css_status', $page->getStatus())->executeStatement();
    }

    public static function flushAll(): void
    {
        self::getQueryBuilder()
            ->update(self::TABLE)
            ->set('critical_css_status', 0)
            ->set('critical_css', '')
            ->executeStatement();
    }

    /** @throws Exception */
    public static function countStatus(): array
    {
        try {
            $data = [
                Page::STATUS_EXPIRED => 0,
                Page::STATUS_PENDING => 0,
                Page::STATUS_ACTUAL => 0,
                Page::STATUS_ERROR => 0,
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
                ->executeQuery()
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
