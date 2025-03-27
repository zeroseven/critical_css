<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Middleware;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheGroupException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use Zeroseven\CriticalCss\Model\Page;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\SettingsService;

class UpdateStyles implements MiddlewareInterface
{
    public const URL_PARAMETER = 'critical_css';
    public const URL_VALUE = 'update';

    public static function createUrl(): string
    {
        return (string)GeneralUtility::makeInstance(Uri::class)
            ?->withScheme(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            ->withHost($_SERVER['HTTP_HOST'] ?? '')
            ->withPath('/')
            ->withQuery(self::URL_PARAMETER . '=' . self::URL_VALUE);
    }

    protected function clearFrontendCache(int $pageUid): void
    {
        try {
            GeneralUtility::makeInstance(CacheManager::class)?->flushCachesInGroupByTags('pages', [
                'ignore_critical_css',
                'pageId_' . $pageUid
            ]);
        } catch (NoSuchCacheGroupException $e) {
        }
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
        if (!trim($request->getUri()->getPath(), '/') && $request->getUri()->getQuery() === self::URL_PARAMETER . '=' . self::URL_VALUE) {
            if (
                $this->getHeader($request, 'X-TOKEN') === SettingsService::getAuthenticationToken()
                && ($body = (string)$request->getBody())
                && ($pageUid = (int)$this->getHeader($request, 'X-PAGE-UID'))
            ) {
                try {
                    $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                    if ($criticalCss = $data['criticalCss'] ?? null) {
                        $pageLanguage = MathUtility::canBeInterpretedAsInteger($language = $this->getHeader($request, 'X-PAGE-LANGUAGE')) ? (int)$language : null;
                        $linkedStyles = ($uncriticalStyles = $data['uncriticalCss'] ?? null)
                            ? GeneralUtility::writeStyleSheetContentToTemporaryFile('\/*uncritical css styles*\/' . $uncriticalStyles)
                            : '';

                        // Update database
                        DatabaseService::update(Page::makeInstance()
                            ->setUid($pageUid)
                            ->setLanguage($pageLanguage)
                            ->setStatus(Page::STATUS_ACTUAL)
                            ->setInlineStyles($criticalCss)
                            ->setLinkedStyles($linkedStyles));

                        // Clear frontend cache
                        $this->clearFrontendCache($pageUid);

                        // Send response
                        return $this->sendJsonResponse(true);
                    }
                } catch (JsonException) {
                }
            }

            // Send response
            return $this->sendJsonResponse(false);
        }

        // Ciao, greetings to the others ... 👋
        return $handler->handle($request);
    }
}
