<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use Zeroseven\CriticalCss\Middleware\UpdateStyles;
use Zeroseven\CriticalCss\Model\Styles;

class RequestService
{
    protected const URL = 'http://64.225.109.175:8055/custom/ccss/v1/generate/';

    protected static function getCallbackUrl(): string
    {
        return (string)GeneralUtility::makeInstance(Uri::class)
            ->withScheme(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            ->withHost($_SERVER['HTTP_HOST'])
            ->withPath(UpdateStyles::PATH);
    }

    protected static function getPageUrl(Styles $styles): string
    {
        return urlencode(GeneralUtility::makeInstance(UriBuilder::class)->reset()->setCreateAbsoluteUri(true)->setTargetPageUid($styles->getUid())->build());
    }

    public static function send(Styles $styles): ResponseInterface
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class)->createRequest('get', self::URL)
            ->withHeader('X-TOKEN', SettingsService::getAuthenticationToken())
            ->withHeader('X-URL', self::getPageUrl($styles))
            ->withHeader('X-CALLBACK', self::getCallbackUrl())
            ->withHeader('X-PAGE_UID', (string)$styles->getUid());

        return GuzzleClientFactory::getClient()->send($request);
    }
}
