<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageRendererHook
{
    protected function isFrontend(): bool
    {
        return isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }

    protected function getCriticalCss(): ?string
    {
        if (
            $GLOBALS['TSFE'] && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && ($row = $GLOBALS['TSFE']->page)
            && isset($row['critical_css_disabled'], $row['critical_css'])
            && empty($row['critical_css_disabled'])
            && !empty($criticalCss = $row['critical_css'])
        ) {
            return $criticalCss;
        }

        return null;
    }

    public function addCriticalCss(array &$params): void
    {
        if ($this->isFrontend() && $criticalCss = $this->getCriticalCss()) {

            // Move all styles to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*<![CDATA[*/<!--/*z7_critical_css*/ ' . $criticalCss . ' -->/*]]>*/</style>';
        }
    }
}
