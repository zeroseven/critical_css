<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class Styles
{
    public CONST STATUS_EXPIRED = 0;

    public CONST STATUS_PENDING = 1;

    public CONST STATUS_ACTUAL = 2;

    public CONST STATUS_ERROR = 3;

    /** @var int */
    protected $uid;

    /** @var int */
    protected $language;

    /** @var string */
    protected $css;

    /** @var bool */
    protected $disabled;

    /** @var int */
    protected $status;

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

    public function setUid(int $uid): self
    {
        $this->uid = $uid;
        return $this;
    }

    public function getLanguage(): int
    {
        return $this->language;
    }

    public function setLanguage(int $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getCss(): string
    {
        return $this->css;
    }

    public function setCss(string $css): self
    {
        $this->css = $css;
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
}
