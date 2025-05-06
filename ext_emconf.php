<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Critical css',
    'description' => 'Extracts and delivers above-the-fold CSS per page – fast, smart, and dynamic. Boost your page speed and reduce CLS with automated critical CSS styles.',
    'category' => 'fe',
    'author' => 'Raphael Thanner',
    'author_email' => 'r.thanner@zeroseven.de',
    'author_company' => 'zeroseven design studios GmbH',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'dashboard' => ''
        ]
    ]
];
