<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zeroseven\CriticalCss\Middleware\UpdateStyles;
use Zeroseven\CriticalCss\Model\Page;

class RequestService
{
    protected const URL = 'http://64.225.109.175:8055/custom/ccss/v1/generate';

    protected static function getCallbackUrl(): string
    {
        return (string)GeneralUtility::makeInstance(Uri::class)
            ->withScheme(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            ->withHost($_SERVER['HTTP_HOST'])
            ->withPath(UpdateStyles::PATH);
    }

    protected static function getPageUrl(Page $page): string
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(UriBuilder::class)->reset()->setCreateAbsoluteUri(true)->setTargetPageUid($page->getUid())->build();
    }

    public static function send(string $css, Page $page): void
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class)->createRequest('post', self::URL)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('X-TOKEN', SettingsService::getAuthenticationToken())
            ->withHeader('X-URL', self::getPageUrl($page))
            ->withHeader('X-CALLBACK', self::getCallbackUrl())
            ->withHeader('X-PAGE-UID', (string)$page->getUid())
            ->withHeader('X-PAGE-LANGUAGE', (string)$page->getLanguage());

        try {
            GuzzleClientFactory::getClient()->send($request, ['body' => $css]);
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_PENDING));
        } catch (GuzzleException $e) {
            DatabaseService::updateStatus($page->setStatus(Page::STATUS_ERROR));
            LogService::systemError(sprintf("%s. HTTP headers: %s. Body: %sb", $e->getMessage(), json_encode(array_diff_key($request->getHeaders(), ['X-TOKEN' => false])), mb_strlen($css)));
        }
    }
}
