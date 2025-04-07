<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class CriticalCssRequierdEvent
{
    protected bool $requiered = true;
    protected ServerRequestInterface $request;
    protected TypoScriptFrontendController $typoScriptFrontendController;

    public function __construct(ServerRequestInterface $request, TypoScriptFrontendController $typoScriptFrontendController)
    {
        $this->request = $request;
        $this->typoScriptFrontendController = $typoScriptFrontendController;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $this->typoScriptFrontendController;
    }

    public function isRequiered(): bool
    {
        return $this->requiered;
    }

    public function setRequiered(bool $requiered): void
    {
        $this->requiered = $requiered;
    }
}
