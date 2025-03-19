<?php

return [
    'frontend' => [
        'zeroseven/critical_css/update_styles' => [
            'target' => \Zeroseven\CriticalCss\Middleware\UpdateStyles::class,
            'before' => [
                'typo3/cms-frontend/eid'
            ]
        ]
    ]
];
