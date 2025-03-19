<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class Page
{
    public const STATUS_EXPIRED = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_ACTUAL = 2;
    public const STATUS_ERROR = 3;

    protected int $uid;
    protected ?int $language;
    protected bool $disabled;
    protected int $status;

    // The critical css
    protected ?string $inlineStyles;

    // The uncritical css
    protected ?string $linkedStyles;

    public function __construct(?array $row = null)
    {
        if (empty($row) && ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController) {
            $row = $GLOBALS['TSFE']->page;
        }

        $this->uid = (int)($row['uid'] ?? 0);
        $this->language = isset($row['sys_language_uid']) ? (int)$row['sys_language_uid'] : null;
        $this->disabled = (bool)($row['critical_css_disabled'] ?? false);
        $this->status = (int)($row['critical_css_status'] ?? 0);
        $this->inlineStyles = (string)($row['critical_css_inline'] ?? '');
        $this->linkedStyles = (string)($row['critical_css_linked'] ?? '');
    }

    public static function makeInstance(?array $row = null): self
    {
        return GeneralUtility::makeInstance(self::class, $row);
    }

    public function toArray(): array
    {
        return [
            'critical_css_disabled' => $this->isDisabled(),
            'critical_css_status' => $this->getStatus(),
            'critical_css_inline' => $this->getInlineStyles(),
            'critical_css_linked' => $this->getLinkedStyles()
        ];
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): self
    {
        $this->uid = $uid;
        return $this;
    }

    public function getLanguage(): ?int
    {
        return $this->language;
    }

    public function setLanguage(?int $language = null): self
    {
        $this->language = $language;
        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function isEnabled(): bool
    {
        return !$this->disabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->disabled = !$enabled;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getInlineStyles(): ?string
    {
        return $this->inlineStyles;
    }

    public function setInlineStyles(string $css = null): self
    {
        $this->inlineStyles = $css;
        return $this;
    }

    public function getLinkedStyles(): ?string
    {
        return $this->linkedStyles;
    }

    public function setLinkedStyles(string $css = null): self
    {
        $this->linkedStyles = $css;
        return $this;
    }
}
