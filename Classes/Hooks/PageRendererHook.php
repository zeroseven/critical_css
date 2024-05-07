<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\CriticalCss\Model\CriticalCss;
use Zeroseven\CriticalCss\Service\RequestService;
use Zeroseven\CriticalCss\Service\SettingsService;

class PageRendererHook
{
    protected CriticalCss $criticslCss;

    public function __construct()
    {
        $this->criticslCss = CriticalCss::makeInstance();
    }

    protected function ready(): bool
    {
        return

            // Access to global parameters
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController

            // Check application type
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()

            // No frontend user or backend user logged in
            && empty($GLOBALS['TSFE']->fe_user->user)
            && empty($GLOBALS['BE_USER'])

            // Check for default page type
            && (int)$GLOBALS['TSFE']->type === 0

            // The page is not disabled for critical styles
            && $this->criticslCss->isEnabled()

            // There is no error on the page
            && $this->criticslCss->getStatus() !== CriticalCss::STATUS_ERROR

            // The service is enabled
            && SettingsService::isEnabled()

            // An authentication key is configured
            && SettingsService::getAuthenticationToken();
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
            if (empty($cssFile['allWrap'] ?? null)
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

    protected function cssInlineToTemporaryFile(array &$params, PageRenderer $pageRenderer): void
    {
        $styles = '';

        foreach ($params['cssInline'] ?? [] as $key => $value) {
            if ($params['cssInline'][$key]['code'] ?? null) {
                $styles .= '/* cssInline: ' . $key . ' */' . LF . $params['cssInline'][$key]['code'] . LF;
                unset($params['cssInline'][$key]);
            }
        }

        if ($styles) {
            $path = GeneralUtility::writeStyleSheetContentToTemporaryFile($styles);
            $pageRenderer->addCssFile($path, 'stylesheet', 'all', 'css inline styles', null, false, null, false);
        }
    }

    protected function renderCriticalCss(array &$params): void
    {
        if ($criticalCss = $this->criticslCss->getCss()) {

            // Move all css files to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*z7_critical_css*/' . $criticalCss . '</style>';
        }
    }

    /** @throws \JsonException */
    public function preProcess(array &$params, PageRenderer $pageRenderer): void
    {
        if ($this->ready()) {
            if ($this->criticslCss->getStatus() === CriticalCss::STATUS_EXPIRED && $css = $this->collectCss($params)) {
                RequestService::send($css, $this->criticslCss);
            }

            if ($this->criticslCss->getCss()) {
                $this->cssInlineToTemporaryFile($params, $pageRenderer);
            }
        }
    }

    public function postProcess(array &$params): void
    {
        if ($this->ready()) {
            $this->renderCriticalCss($params);
        }
    }
}
