<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\ConsumableString;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use Zeroseven\CriticalCss\Model\Page;
use Zeroseven\CriticalCss\Event\CriticalCssRequierdEvent;
use Zeroseven\CriticalCss\Service\RequestService;
use Zeroseven\CriticalCss\Service\SettingsService;

class PageRendererHook
{
    protected Page $page;
    protected EventDispatcher $eventDispatcher;

    public function __construct()
    {
        $this->page = Page::makeInstance();
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
    }

    protected function getRequest(): ?ServerRequestInterface
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            ? $GLOBALS['TYPO3_REQUEST']
            : null;
    }

    protected function isRequiered(): bool
    {
        return

            // Access to global parameters
            ($request = $this->getRequest())
            && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController

            // Check application type
            && ApplicationType::fromRequest($request)->isFrontend()

            // No frontend user or backend user logged in
            && ($context = GeneralUtility::makeInstance(Context::class))
            && $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') === false
            && $context->getPropertyFromAspect('backend.user', 'isLoggedIn') === false

            // Check for default page type
            && (int)$request->getAttribute('routing')->getPageType() === 0

            // The page is not disabled for critical styles
            && $this->page->isEnabled()

            // There is no error on the page
            && $this->page->getStatus() !== Page::STATUS_ERROR

            // The service is enabled
            && SettingsService::isEnabled()

            // An authentication key is configured
            && SettingsService::getAuthenticationToken()

            // Register your own event
            && $this->eventDispatcher->dispatch(new CriticalCssRequierdEvent($request, $GLOBALS['TSFE']))->isRequiered();
    }

    protected function getNonce(): ?string
    {
        return ($nonceAttribute = $this->getRequest()?->getAttribute('nonce'))
            && ($nonceAttribute instanceof ConsumableString)
                ? $nonceAttribute->consume()
                : null;
    }

    protected function getAbsoluteFilePath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (($file = GeneralUtility::getFileAbsFileName($path)) && file_exists($file)) {
            return $file;
        }

        if (PathUtility::isAbsolutePath($path) && ($file = Environment::getPublicPath() . '/' . ltrim($path, '/')) && file_exists($file)) {
            return $file;
        }

        return null;
    }

    protected function collectCss(array $params): ?string
    {
        $styles = [];

        // Collect included files
        foreach ($params['cssFiles'] ?? [] as $cssFile) {
            if (
                empty($cssFile['allWrap'])
                && preg_match(SettingsService::getAllowedMediaTypes(), $cssFile['media'])
                && ($file = $this->getAbsoluteFilePath($cssFile['file'] ?? null))
                && $content = file_get_contents($file)
            ) {
                ($cssFile['forceOnTop'] ?? null) ? array_unshift($styles, $content) : array_push($styles, $content);
            }
        }

        // Collect inline styles
        foreach ($params['cssInline'] ?? [] as $key => $value) {
            if ($params['cssInline'][$key]['code'] ?? null) {
                $styles[] = $params['cssInline'][$key]['code'];
            }
        }

        // Collect additional styles
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $assets = $assetCollector->getStyleSheets();
        foreach ($assets as $asset) {
            if ($asset['source']) {
                $styles[] = file_get_contents($asset['source']);
            }
        }

        // Todo: inlcude css libs

        return count($styles) ? implode(LF, $styles) : null;
    }

    protected function renderCriticalCss(array &$params): void
    {
        if ($criticalCss = $this->page->getInlineStyles()) {
            $nonceAttribute = $nonce = $this->getNonce() ? ' nonce="' . $this->getNonce() . '"' : '';

            $params['cssFiles'] = '';
            $params['cssInline'] = '<style' . $nonceAttribute . '>/*critical css styles*/' . $criticalCss . '</style>';

            if ($linkedStyles = $this->page->getLinkedStyles()) {
                $params['footerData'][] = '<link rel="stylesheet" href="' . $linkedStyles . '" media="all"/>';
            }
        }
    }

    /** @throws \JsonException */
    public function preProcess(array &$params): void
    {
        if ($this->isRequiered()) {
            if ($this->page->getStatus() === Page::STATUS_EXPIRED && $css = $this->collectCss($params)) {
                RequestService::send($css, $this->page);
            }
        }
    }

    public function postProcess(array &$params): void
    {
        if ($this->isRequiered()) {
            $this->renderCriticalCss($params);
        }
    }

    public static function register(): void
    {
        $extenionKey = SettingsService::getExtensionKey();

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][$extenionKey] = static::class . '->postProcess';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][$extenionKey] = static::class . '->preProcess';
    }
}
