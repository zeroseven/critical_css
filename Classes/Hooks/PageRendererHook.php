<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\CriticalCss\Model\Styles;
use Zeroseven\CriticalCss\Service\RequestService;
use Zeroseven\CriticalCss\Service\SettingsService;

class PageRendererHook
{
    protected Styles $styles;

    public function __construct()
    {
        $this->styles = Styles::makeInstance();
    }

    protected function needCriticalCss(): bool
    {
        return

            // Check application request
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()

            // Check for default page type
            && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController
            && (int)$GLOBALS['TSFE']->type === 0

            // Page is not disabled for critical styles
            && $this->styles->isEnabled()

            // The service is enabled
            && SettingsService::isEnabled()

            // An authentication key is configured
            && SettingsService::getAuthenticationToken();
    }

    protected function handleCriticalCss(): ?string
    {
        if ($this->styles->getStatus() === Styles::STATUS_ACTUAL) {
            return $this->styles->getCss();
        }

        if ($this->styles->getStatus() === Styles::STATUS_EXPIRED) {
            RequestService::send($this->styles);

            return $this->styles->getCss();
        }

        return null;
    }

    public function preProcess(array $params, PageRenderer $pageRenderer): void
    {
        // Move inline styles to a temporary file
        if ($this->needCriticalCss()) {
            $styles = '';

            foreach ($params['cssInline'] ?? [] as $key => $value) {
                if ($params['cssInline'][$key]['code'] ?? null) {
                    $styles .= '/* cssInline: ' . $key . ' */' . LF . $params['cssInline'][$key]['code'] . LF;
                    unset($params['cssInline'][$key]);
                }
            }

            if($styles) {
                $path = GeneralUtility::writeStyleSheetContentToTemporaryFile($styles);
                $pageRenderer->addCssFile($path, 'stylesheet', 'all', 'css inline styles', null, true, null, true);
            }
        }
    }

    public function postProcess(array &$params): void
    {
        if ($this->needCriticalCss() && $criticalCss = $this->handleCriticalCss()) {

            // Move all styles to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*z7_critical_css*/' . $criticalCss . '</style>';
        }
    }
}
