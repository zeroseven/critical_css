<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageRendererHook
{
    protected function needCriticalCss(): bool
    {
        return

            // Check for necessary information and context
            isset($GLOBALS['TSFE'], $GLOBALS['TYPO3_REQUEST'])
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface

            // Check application request
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()

            // Check for default page type
            && (int)$GLOBALS['TSFE']->type === 0;
    }

    protected function getCriticalCss(): ?string
    {
        $row = (array)$GLOBALS['TSFE']->page;

        if (isset($row['critical_css_disabled'], $row['critical_css']) && empty($row['critical_css_disabled'])) {
            return $row['critical_css'] ?: null;
        }

        return null;
    }

    public function addCriticalCss(array &$params): void
    {
        if ($this->needCriticalCss() && $criticalCss = $this->getCriticalCss()) {

            // Move all styles to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*<![CDATA[*/<!--/*z7_critical_css*/ ' . $criticalCss . ' -->/*]]>*/</style>';
        }
    }
}
