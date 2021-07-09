<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use Zeroseven\CriticalCss\Model\Styles;

class PageRendererHook
{
    protected Styles $criticalCss;

    public function __construct()
    {
        $this->criticalCss = Styles::makeInstance();
    }

    protected function needCriticalCss(): bool
    {
        return

            // Check application request
            isset($GLOBALS['TYPO3_REQUEST'])
            && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()

            // Check for default page type
            && (int)$GLOBALS['TSFE']->type === 0

            // Page is not disabled for critical styles
            && $this->criticalCss->isEnabled();
    }

    protected function handleCriticalCss(): ?string
    {
        if ($this->criticalCss->getStatus() === Styles::STATUS_ACTUAL) {
            return $this->criticalCss->getCss();
        }

        if($this->criticalCss->getStatus() === Styles::STATUS_PENDING) {
            return null;
        }

        if($this->criticalCss->getStatus() === Styles::STATUS_EXPIRED) {
            // Call styles and set status to 1
            return null;
        }
    }

    public function addCriticalCss(array &$params): void
    {
        if ($this->needCriticalCss() && $criticalCss = $this->handleCriticalCss()) {

            // Move all styles to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*<![CDATA[*/<!--/*z7_critical_css*/ ' . $criticalCss . ' -->/*]]>*/</style>';
        }
    }
}
