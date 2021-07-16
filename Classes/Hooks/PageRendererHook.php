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
    /** @var Styles */
    protected $styles;

    public function __construct()
    {
        $this->styles = Styles::makeInstance();
    }

    protected function ready(): bool
    {
        return

            // Access to global parameters
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController

            // Check application type
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()

            // Check for default page type
            && (int)$GLOBALS['TSFE']->type === 0

            // Page is not disabled for critical styles
            && $this->styles->isEnabled()

            // There is no error on the page
            && $this->styles->getStatus() !== Styles::STATUS_ERROR

            // The service is enabled
            && SettingsService::isEnabled()

            // An authentication key is configured
            && SettingsService::getAuthenticationToken();
    }

    protected function collectCss(array $params): string
    {
        $styles = [];

        // Collect included files
        foreach ($params['cssFiles'] as $key => $value) {
            if (($path = $value['file'] ?? null)
                && ($file = GeneralUtility::getFileAbsFileName($path))
                && file_exists($file)
                && $content = file_get_contents($file)
            ) {
                ($value['forceOnTop'] ?? null) ? array_unshift($styles, $content) : array_push($styles, $content);
            }
        }

        // Collect inline styles
        foreach ($params['cssInline'] ?? [] as $key => $value) {
            if ($params['cssInline'][$key]['code'] ?? null) {
                $styles[] = $params['cssInline'][$key]['code'];
            }
        }

        // Todo: inlcude css libs

        return implode(LF, $styles);
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
            $pageRenderer->addCssFile($path, 'stylesheet', 'all', 'css inline styles', null, true, null, true);
        }
    }

    protected function renderCriticalCss(array &$params): void
    {
        if ($criticalCss = $this->styles->getCss()) {

            // Move all css files to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*z7_critical_css*/' . $criticalCss . '</style>';
        }
    }

    public function preProcess(array &$params, PageRenderer $pageRenderer): void
    {
        if ($this->ready()) {
            if ($this->styles->getStatus() === Styles::STATUS_EXPIRED) {
                RequestService::send($this->collectCss($params), $this->styles);
            }

            if ($this->styles->getCss()) {
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
