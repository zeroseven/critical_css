<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Zeroseven\CriticalCss\Model\Styles;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\SettingsService;

class UpdateStyles implements MiddlewareInterface
{
    public const PATH = '/-/critical_css/receive/';

    protected function clearFrontendCache(int $pageUid): void
    {
        GeneralUtility::makeInstance(CacheManager::class)->flushCachesInGroupByTags('pages', ['ignore_critical_css', 'pageId_' . $pageUid]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($path = $request->getUri()->getPath()) && ($path === self::PATH || $path === self::PATH . '/')) {

            if (
                ($criticalCss = (string)$request->getBody())

                //TODO: && Check authenticationToken

                //TODO: && Get page uid)
                && ($pageUid = 1)
            ) {
                // Update database
                DatabaseService::update(Styles::makeInstance()
                    ->setUid($pageUid)
                    ->setStatus(Styles::STATUS_ACTUAL)
                    ->setCss($criticalCss));

                // Clear frontend cache
                $this->clearFrontendCache($pageUid);
            }

            // Send response
            return new JsonResponse(['success' => true], 200, [
                'cache-control' => 'no-cache, must-revalidate',
                'X-Robots-Tag' => 'noindex',
                'X-Extension' => SettingsService::EXTENSION_KEY
            ]);
        }

        // Ciao, greetings to the others ... 👋
        return $handler->handle($request);
    }
}
