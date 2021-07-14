<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use Zeroseven\CriticalCss\Model\Styles;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\SettingsService;

class UpdateStyles implements MiddlewareInterface
{
    public const PATH = '/-/critical_css/receive/';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($path = $request->getUri()->getPath()) && ($path === self::PATH || $path === self::PATH . '/')) {

            if(($criticalCss = (string)$request->getBody())
                //TODO: && Check authKey
                //TODO: && Get page uid)
            ) {
                // Update database
                DatabaseService::update(Styles::makeInstance()
                    ->setUid(1)
                    ->setStatus(Styles::STATUS_ACTUAL)
                    ->setCss($criticalCss));

                // TODO: clear cache
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
