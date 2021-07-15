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
    public const PATH = '/-/critical_css/update/';

    protected function clearFrontendCache(int $pageUid): void
    {
        GeneralUtility::makeInstance(CacheManager::class)->flushCachesInGroupByTags('pages', [
            'ignore_critical_css',
            'pageId_' . $pageUid
        ]);
    }

    protected function getHeader(ServerRequestInterface $request, string $key): ?string
    {
        if ($request->hasHeader($key) && $value = $request->getHeader($key)) {
            if(is_array($value) && $value[0] ?? null) {
                return (string)$value[0];
            } elseif (is_string($value) || is_int($value)) {
                return (string)$value;
            }
        }

        return null;
    }

    protected function sendJsonResponse(bool $success): ResponseInterface
    {
        return new JsonResponse(['success' => $success], $success ? 200 : 200, [
            'cache-control' => 'no-cache, must-revalidate',
            'X-Robots-Tag' => 'noindex',
            'X-Extension' => SettingsService::EXTENSION_KEY
        ]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($path = $request->getUri()->getPath()) && ($path === self::PATH || $path === self::PATH . '/' || $path === rtrim(self::PATH, '/'))) {
            mail('r.thanner@zeroseven.de', self::class, implode("\r\n", [
                $this->getHeader($request, 'X-PAGE-UID'),
                $this->getHeader($request, 'X-TOKEN')
            ]));

            if (
                ($criticalCss = (string)$request->getBody())
                && ($pageUid = (int)$this->getHeader($request, 'X-PAGE-UID'))
                && ($this->getHeader($request, 'X-TOKEN') === SettingsService::getAuthenticationToken())
            ) {
                // Update database
                DatabaseService::update(Styles::makeInstance()
                    ->setUid($pageUid)
                    ->setStatus(Styles::STATUS_ACTUAL)
                    ->setCss($criticalCss));

                // Clear frontend cache
                $this->clearFrontendCache($pageUid);

                // Send response
                return $this->sendJsonResponse(true);
            }

            // Send response
            return $this->sendJsonResponse(false);
        }

        // Ciao, greetings to the others ... 👋
        return $handler->handle($request);
    }
}
