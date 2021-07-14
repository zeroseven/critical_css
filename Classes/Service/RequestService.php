<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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
        $url = self::URL . self::getPageUrl($styles) . '?callback=' . self::getCallbackUrl();

        $request = GeneralUtility::makeInstance(RequestFactory::class)->createRequest('get', $url)
            ->withHeader('auth_token', SettingsService::getAuthKey())
            ->withHeader('auth_key', SettingsService::getAuthKey())
            ->withHeader('callback_url', self::getCallbackUrl())
            ->withHeader('page_url', self::getPageUrl($styles))
            ->withHeader('page_uid', (string)$styles->getUid());

        return GuzzleClientFactory::getClient()->send($request);
    }
}
