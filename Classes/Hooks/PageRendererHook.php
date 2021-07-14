<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Zeroseven\CriticalCss\Model\Styles;
use Zeroseven\CriticalCss\Service\DatabaseService;
use Zeroseven\CriticalCss\Service\RequestService;

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

            // An authentication key is configured
            && RequestService::getAuthToken();
    }

    protected function handleCriticalCss(): ?string
    {
        if ($this->styles->getStatus() === Styles::STATUS_ACTUAL) {
            return $this->styles->getCss();
        }

        if ($this->styles->getStatus() === Styles::STATUS_PENDING) {
//            DatabaseService::update(Styles::makeInstance()->setCss(':root{background:yellow}'));
//            DatabaseService::updateStatus($this->styles->setStatus(Styles::STATUS_ACTUAL));
            return null;
        }

        if ($this->styles->getStatus() === Styles::STATUS_EXPIRED) {
            DatabaseService::updateStatus($this->styles->setStatus(Styles::STATUS_PENDING));
            return $this->styles->getCss();
        }
    }

    public function preProcess(array $params, PageRenderer $pageRenderer): void
    {
        // Move inline styles to a temporary file
        if ($this->needCriticalCss() && $this->styles->getCss()) {
            $styles = '';

            foreach ($params['cssInline'] ?? [] as $key => $value) {
                if ($params['cssInline'][$key]['code'] ?? null) {
                    $styles .= $params['cssInline'][$key]['code'];
                    unset($params['cssInline'][$key]);
                }
            }

            if($styles) {
                $path = GeneralUtility::writeStyleSheetContentToTemporaryFile('/* cssInline start */' . LF . $styles . LF . '/* cssInline end */');
                $pageRenderer->addCssFile($path, null, null, 'css inline styles', null, true, null, true);
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
            $params['cssInline'] .= '<style>/*<![CDATA[*/<!--/*z7_critical_css*/ ' . LF . $criticalCss . LF . ' -->/*]]>*/</style>';
        }
    }
}
