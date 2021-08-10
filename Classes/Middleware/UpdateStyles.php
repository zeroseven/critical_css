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
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\CriticalCss\Model\Page;
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
            if (is_array($value) && $value[0] ?? null) {
                return $value[0];
            }

            if (is_string($value) || is_int($value)) {
                return (string)$value;
            }
        }

        return null;
    }

    protected function sendJsonResponse(bool $success): ResponseInterface
    {
        return new JsonResponse(['success' => $success], $success ? 200 : 400, [
            'cache-control' => 'no-cache, must-revalidate',
            'X-Robots-Tag' => 'noindex',
            'X-Extension' => SettingsService::EXTENSION_KEY
        ]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($path = $request->getUri()->getPath()) && ($path === self::PATH || $path === self::PATH . '/' || $path === rtrim(self::PATH, '/'))) {
            if (
                $this->getHeader($request, 'X-TOKEN') === SettingsService::getAuthenticationToken()
                && ($criticalCss = (string)$request->getBody())
                && ($pageUid = (int)$this->getHeader($request, 'X-PAGE-UID'))
            ) {
                $pageLanguage = MathUtility::canBeInterpretedAsInteger($language = $this->getHeader($request, 'X-PAGE-LANGUAGE')) ? (int)$language : null;

                // Update database
                DatabaseService::update(Page::makeInstance()
                    ->setUid($pageUid)
                    ->setLanguage($pageLanguage)
                    ->setStatus(Page::STATUS_ACTUAL)
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
