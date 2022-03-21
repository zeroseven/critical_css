<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\Model\CriticalCss;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\SettingsService;

class DataHandlerHook
{
    protected function contentMoved(array $params, DataHandler $dataHandler): ?CriticalCss
    {
        if (isset($dataHandler->cmdmap[$params['table']][$params['uid']]['move']) && $pageUid = (int)$params['uid_page']) {
            return CriticalCss::makeInstance()->setUid($pageUid)->setLanguage(null);
        }

        return null;
    }

    protected function contentUpdated(array $params, DataHandler $dataHandler): ?CriticalCss
    {
        if ($params['table'] === 'tt_content' && empty($dataHandler->cmdmap) && $pageUid = (int)$params['uid_page']) {
            $languageField = $GLOBALS['TCA'][$params['table']]['ctrl']['languageField'];
            $pageLanguage = $dataHandler->datamap[$params['table']][$params['uid']][$languageField] ?? null;

            return CriticalCss::makeInstance()->setUid($pageUid)->setLanguage($pageLanguage === null ? null : (int)$pageLanguage);
        }

        return null;
    }

    protected function pageUpdated(array $params): ?CriticalCss
    {
        if ($params['table'] === 'pages' && $pageUid = (int)$params['uid_page']) {
            if ($pageUid === (int)$params['uid']) {
                return CriticalCss::makeInstance()->setUid($pageUid)->setLanguage(0);
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($params['table']);
            $queryBuilder->getRestrictions()->removeAll();
            $languageUids = $queryBuilder
                ->select($GLOBALS['TCA'][$params['table']]['ctrl']['languageField'])
                ->from($params['table'])
                ->where($queryBuilder->expr()->eq('uid', (int)$params['uid']))
                ->setMaxResults(1)
                ->execute()
                ->fetchFirstColumn();

            return CriticalCss::makeInstance()->setUid($pageUid)->setLanguage(empty($languageUids) ? null : (int)$languageUids[0]);
        }

        return null;
    }

    protected function pageFlushed(array $params): ?CriticalCss
    {
        if ($pageUid = (int)($params['cacheCmd'] ?? 0)) {
            return CriticalCss::makeInstance()->setUid((int)$pageUid)->setLanguage(null);
        }

        return null;
    }

    public function clearCachePostProc(array &$params, DataHandler $dataHandler): void
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

        // Content element was moved
        if ($criticalCss = $this->contentMoved($params, $dataHandler)) {
            DatabaseService::updateStatus($criticalCss->setStatus(CriticalCss::STATUS_EXPIRED));
        }

        // Content element has been updated
        if ($criticalCss = $this->contentUpdated($params, $dataHandler)) {
            DatabaseService::updateStatus($criticalCss->setStatus(CriticalCss::STATUS_EXPIRED));
        }

        // CriticalCss has been updated
        if ($criticalCss = $this->pageUpdated($params)) {
            DatabaseService::updateStatus($criticalCss->setStatus(CriticalCss::STATUS_EXPIRED));
        }

        // The "clear page cache" button was pressed
        if ($criticalCss = $this->pageFlushed($params)) {
            DatabaseService::updateStatus($criticalCss->setStatus(CriticalCss::STATUS_EXPIRED));
        }

        // Reset all critical styles.
        if ($cacheCmd === ClearCacheToolbarItemHook::CACHE_CMD || $cacheCmd === 'all') {
            DatabaseService::flushAll();
        }
    }
}
