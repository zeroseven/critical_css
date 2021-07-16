<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Widgets\Provider;

use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\CriticalCss\Model\Styles;
use Zeroseven\CriticalCss\Service\DatabaseService;

class StatusDataProvider implements ChartDataProviderInterface
{
    public function getChartData(): array
    {
        $result = DatabaseService::countStatus();

        return [
            'labels' => array_map(static function ($status) {
                return LocalizationUtility::translate('LLL:EXT:z7_critical_css/Resources/Private/Language/locallang_be.xlf:widget.criticalCssStatus.label.' . $status);
            }, array_keys($result)),
            'datasets' => [
                [
                    'backgroundColor' => [
                        Styles::STATUS_EXPIRED => '#ff8700',
                        Styles::STATUS_PENDING => '#6daae0',
                        Styles::STATUS_ACTUAL => '#79a548',
                        Styles::STATUS_ERROR => '#e74c3c'
                    ],
                    'data' => array_values($result)
                ]
            ]
        ];
    }
}
