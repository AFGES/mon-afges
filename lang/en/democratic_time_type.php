<?php

declare(strict_types=1);

return [
    'label' => 'Democratic time type',
    'plural' => 'Democratic time types',
    'fields' => [
        'name' => 'Name',
        'acronym' => 'Acronym',
        'convocation_interval_days' => 'Convocation period (days)',
        'sessions_count' => 'Number of sessions',
        'calculate_quorum_per_college' => 'Calculate quorum per college',
        'quorum_percentage' => 'Required quorum percentage',
        'include_proxies_in_quorum' => 'Include proxies in quorum',
    ],
    'suffixes' => [
        'days' => 'days',
    ],
    'sections' => [
        'quorum_configuration' => 'Quorum configuration',
    ],
];
