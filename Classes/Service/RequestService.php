<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use Zeroseven\CriticalCss\Middleware\UpdateStyles;
use Zeroseven\CriticalCss\Model\Page;

class RequestService
{
    protected const URL = 'http://sleepy-dusk-xckctxbyvt.ploi.team/api/v2/generate';

    protected static function getPageUrl(Page $page): string
    {
        $cObj = GeneralUtility::makeInstance(className: ContentObjectRenderer::class);

        return $cObj->typoLink_URL(['parameter' => $page->getUid(), 'forceAbsoluteUrl' => true]);
    }

    protected static function getVersion(): string
    {
        $_EXTKEY = SettingsService::getExtensionKey();

        if (($path = ExtensionManagementUtility::extPath($_EXTKEY, 'ext_emconf.php')) && file_exists($path)) {
            include $path;

            return $EM_CONF[$_EXTKEY]['version'] ?? '';
        }

        return '';
    }

    /** @throws \JsonException */
    public static function send(string $css, Page $page): void
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class)?->createRequest('post', self::URL)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('X-TOKEN', SettingsService::getAuthenticationToken())
            ->withHeader('X-AUTH-USER', SettingsService::getBasicAuthUsername())
            ->withHeader('X-AUTH-PASSWORD', SettingsService::getBasicAuthPassword())
            ->withHeader('X-URL', self::getPageUrl($page))
            ->withHeader('X-CALLBACK', UpdateStyles::createUrl())
            ->withHeader('X-VERSION', self::getVersion())
            ->withHeader('X-PAGE-UID', (string)$page->getUid())
            ->withHeader('X-PAGE-LANGUAGE', (string)$page->getLanguage());

        try {
            GeneralUtility::makeInstance(GuzzleClientFactory::class)?->getClient()->send($request, ['body' => $css]);
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_PENDING));
        } catch (GuzzleException $e) {
            LogService::systemError(sprintf("%s. HTTP headers: %s. Body: %sb", $e->getMessage(), json_encode(array_diff_key($request->getHeaders(), ['X-TOKEN' => false]), JSON_THROW_ON_ERROR), mb_strlen($css)));
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_ERROR));
        }
    }
}
