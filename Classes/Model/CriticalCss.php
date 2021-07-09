<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class CriticalCss
{
    public CONST STATUS_EXPIRED = 0;

    public CONST STATUS_PENDING = 1;

    public CONST STATUS_ACTUAL = 2;

    protected string $css = '';

    protected bool $disabled = true;

    protected int $status = 0;

    public function __construct(array $row = null)
    {
        if ($row === null && $GLOBALS['TSFE'] && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $row = (array)$GLOBALS['TSFE']->page;
        }

        if (isset($row['critical_css'], $row['critical_css_disabled'], $row['critical_css_status'])) {
            $this->setCss((string)$row['critical_css']);
            $this->setDisabled((bool)$row['critical_css_disabled']);
            $this->setStatus((int)$row['critical_css_status']);
        }
    }

    public static function makeInstance(array $row = null): self
    {
        return GeneralUtility::makeInstance(self::class, $row);
    }

    public function toArray(): array
    {
        return [
            'critical_css' => $this->getCss(),
            'critical_css_disabled' => $this->getDisabled(),
            'critical_css_status' => $this->getStatus()
        ];
    }

    public function getCss(): string
    {
        return $this->css;
    }

    public function setCss(string $css): void
    {
        $this->css = $css;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isEnabled(): bool
    {
        return !$this->disabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->disabled = !$enabled;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
