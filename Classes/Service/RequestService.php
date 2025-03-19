<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use Zeroseven\CriticalCss\Middleware\UpdateStyles;
use Zeroseven\CriticalCss\Model\Page;

class RequestService
{
    protected const URL = 'http://sleepy-dusk-xckctxbyvt.ploi.team/api/v2/generate';

    protected static function getCallbackUrl(): string
    {
        return (string)GeneralUtility::makeInstance(Uri::class)
            ?->withScheme(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            ->withHost($_SERVER['HTTP_HOST'] ?? '')
            ->withPath(UpdateStyles::PATH);
    }

    protected static function getPageUrl(Page $page): string
    {
        return GeneralUtility::makeInstance(UriBuilder::class)?->reset()->setCreateAbsoluteUri(true)->setTargetPageUid($page->getUid())->build();
    }

    /** @throws \JsonException */
    public static function send(string $css, Page $page): void
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class)?->createRequest('post', self::URL)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('X-TOKEN', SettingsService::getAuthenticationToken())
            ->withHeader('X-URL', self::getPageUrl($page))
            ->withHeader('X-CALLBACK', self::getCallbackUrl())
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
