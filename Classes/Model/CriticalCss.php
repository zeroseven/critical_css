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

    protected int $uid;

    protected int $language;

    protected string $css;

    protected bool $disabled;

    protected int $status;

    public function __construct(array $row = null)
    {
        if (empty($row) && $GLOBALS['TSFE'] && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $row = (array)$GLOBALS['TSFE']->page;
        }

        $this->uid = (int)($row['uid'] ?: 0);
        $this->language = (int)($row['sys_language_uid'] ?: 0);
        $this->css = (string)($row['critical_css'] ?: '');
        $this->disabled = (bool)($row['critical_css_disabled'] ?: false);
        $this->status = (int)($row['critical_css_status'] ?: 0);
    }

    public static function makeInstance(array $row = null): self
    {
        return GeneralUtility::makeInstance(self::class, $row);
    }

    public function toArray(): array
    {
        return [
            'critical_css' => $this->getCss(),
            'critical_css_disabled' => $this->isDisabled(),
            'critical_css_status' => $this->getStatus()
        ];
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    public function getLanguage(): int
    {
        return $this->language;
    }

    public function setLanguage(int $language): void
    {
        $this->language = $language;
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
