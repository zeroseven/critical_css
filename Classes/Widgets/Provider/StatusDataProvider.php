<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Widgets\Provider;

use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Zeroseven\CriticalCss\Model\Page;
use Zeroseven\CriticalCss\Service\DatabaseService;

class StatusDataProvider implements ChartDataProviderInterface
{
    public function getChartData(): array
    {
        $result = DatabaseService::countStatus();

        return [
            'labels' => array_map(static function ($status) {
                return LocalizationUtility::translate('LLL:EXT:critical_css/Resources/Private/Language/locallang_be.xlf:widget.criticalCssStatus.label.' . $status);
            }, array_keys($result)),
            'datasets' => [
                [
                    'backgroundColor' => [
                        Page::STATUS_EXPIRED => '#ff8700',
                        Page::STATUS_PENDING => '#6daae0',
                        Page::STATUS_ACTUAL => '#79a548',
                        Page::STATUS_ERROR => '#e74c3c'
                    ],
                    'data' => array_values($result)
                ]
            ]
        ];
    }
}
