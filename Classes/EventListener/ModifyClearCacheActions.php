<?php

namespace Zeroseven\CriticalCss\EventListener;

use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\Service\SettingsService;

final class ModifyClearCacheActions
{
    public const CACHE_CMD = 'critical_css';

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function isAdmin(): bool
    {
        return $this->getBackendUser()->isAdmin();
    }

    protected function isEnabled(bool $fallback = false): bool
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        return (bool)($userTsConfig['options.']['clearCache.']['criticalCss'] ?? $fallback);
    }

    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        if (SettingsService::isEnabled() && ($this->isEnabled() || $this->isAdmin() && $this->isEnabled(true))) {
            $event->addCacheAction([
                'id' => 'critical_css',
                'title' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_be.xlf:flushCache.title',
                'description' => 'LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_be.xlf:flushCache.description',
                'href' => (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('tce_db', ['cacheCmd' => self::CACHE_CMD]),
                'iconIdentifier' => 'apps-toolbar-menu-cache',
            ]);

            $event->addCacheActionIdentifier(self::CACHE_CMD);
        }
    }
}
