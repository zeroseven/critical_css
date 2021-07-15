<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Widgets\Provider;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use Zeroseven\CriticalCss\Service\DatabaseService;

class StatusDataProvider implements ChartDataProviderInterface
{
    private LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function getChartData(): array
    {
        return [];
    }

}
