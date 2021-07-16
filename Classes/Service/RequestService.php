<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use GuzzleHttp\Exception\GuzzleException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SysLog\Action as SystemLogAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogError;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
        return GeneralUtility::makeInstance(ObjectManager::class)->get(UriBuilder::class)->reset()->setCreateAbsoluteUri(true)->setTargetPageUid($styles->getUid())->build();
    }

    public static function send(string $css, Styles $styles): void
    {
        $request = GeneralUtility::makeInstance(RequestFactory::class)->createRequest('post', self::URL)
            ->withHeader('X-TOKEN', SettingsService::getAuthenticationToken())
            ->withHeader('X-URL', self::getPageUrl($styles))
            ->withHeader('X-CALLBACK', self::getCallbackUrl())
            ->withHeader('X-PAGE-UID', (string)$styles->getUid())
            ->withBody(new Stream($css));

        try {
            GuzzleClientFactory::getClient()->send($request);
            DatabaseService::updateStatus($styles->setStatus(Styles::STATUS_PENDING));
        } catch (GuzzleException $e) {
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_log')->insert('sys_log',[
                'type' => SystemLogType::ERROR,
                'action' => SystemLogAction::UNDEFINED,
                'error' => SystemLogError::SYSTEM_ERROR,
                'details' => $e->getMessage() . '. HTTP headers: ' . json_encode($request->getHeaders()),
                'tstamp' => time()
            ]);
        }
    }
}
