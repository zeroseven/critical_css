<?php

return [
    'frontend' => [
        'zeroseven/z7_critical_css/update_styles' => [
            'target' => \Zeroseven\CriticalCss\Middleware\UpdateStyles::class,
            'before' => [
                'typo3/cms-frontend/eid'
            ]
        ]
    ]
];
