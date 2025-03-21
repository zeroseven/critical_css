<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\EventListener\ModifyClearCacheActions;
use Zeroseven\CriticalCss\Model\Page;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\SettingsService;

class DataHandlerHook
{
    protected function contentMoved(array $params, DataHandler $dataHandler): ?Page
    {
        if (isset($params['table'], $params['uid'], $params['uid_page'], $dataHandler->cmdmap[$params['table']][$params['uid']]['move']) && $pageUid = (int)$params['uid_page']) {
            return Page::makeInstance()->setUid($pageUid)->setLanguage(null);
        }

        return null;
    }

    protected function contentUpdated(array $params, DataHandler $dataHandler): ?Page
    {
        if (($params['table'] ?? null) === 'tt_content' && empty($dataHandler->cmdmap) && $pageUid = (int)($params['uid_page'] ?? 0)) {
            $languageField = $GLOBALS['TCA'][$params['table']]['ctrl']['languageField'];
            $pageLanguage = $dataHandler->datamap[$params['table']][$params['uid']][$languageField] ?? null;

            return Page::makeInstance()->setUid($pageUid)->setLanguage($pageLanguage === null ? null : (int)$pageLanguage);
        }

        return null;
    }

    /** @throws Exception */
    protected function pageUpdated(array $params): ?Page
    {
        if (($table = $params['table'] ?? null) === 'pages' && $pageUid = (int)($params['uid_page'] ?? 0)) {
            if ($pageUid === (int)($params['uid'] ?? 0)) {
                return Page::makeInstance()->setUid($pageUid)->setLanguage(0);
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $languageUids = $queryBuilder
                ->select($GLOBALS['TCA'][$table]['ctrl']['languageField'])
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', (int)$params['uid']))
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchFirstColumn();

            return Page::makeInstance()->setUid($pageUid)->setLanguage(empty($languageUids) ? null : (int)$languageUids[0]);
        }

        return null;
    }

    protected function pageFlushed(array $params): ?Page
    {
        if ($pageUid = (int)($params['cacheCmd'] ?? 0)) {
            return Page::makeInstance()->setUid((int)$pageUid)->setLanguage(null);
        }

        return null;
    }

    /** @throws Exception */
    public function clearCachePostProc(array &$params, DataHandler $dataHandler): void
    {
        $cacheCmd = $params['cacheCmd'] ?? null;

        // Do nothing on some conditions
        if (
            ($params['tags']['ignore_critical_css'] ?? false) // Ignore critical css styles from flushing cache ...
            || ($cacheCmd === 'pages' || $cacheCmd === 'lowlevel')  // It's the "flush frontend cache" command ...
            || SettingsService::isDisabled() // Service is disabled ...
        ) {
            return;
        }

        // Content element was moved
        if ($page = $this->contentMoved($params, $dataHandler)) {
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_EXPIRED));
        }

        // Content element has been updated
        if ($page = $this->contentUpdated($params, $dataHandler)) {
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_EXPIRED));
        }

        // CriticalCss has been updated
        if ($page = $this->pageUpdated($params)) {
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_EXPIRED));
        }

        // The "clear page cache" button was pressed
        if ($page = $this->pageFlushed($params)) {
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_EXPIRED));
        }

        // Reset all critical styles.
        if ($cacheCmd === ModifyClearCacheActions::CACHE_CMD || $cacheCmd === 'all') {
            DatabaseService::flushAll();
        }
    }

    public static function register(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][SettingsService::getExtensionKey()] = static::class . '->clearCachePostProc';
    }
}
