<?php

declare(strict_types=1);

return [
    'label' => 'Democratic time session',
    'plural' => 'Democratic time sessions',
    'fields' => [
        'democratic_time_type' => 'Democratic time type',
        'started_at' => 'Start date and time',
        'ended_at' => 'End date and time',
        'location' => 'Location',
        'status' => 'Status',
        'sign_in_opens_at' => 'Sign-in opens at',
        'sign_in_closes_at' => 'Sign-in closes at',
        'auto_open_sign_in' => 'Automatic opening',
        'minutes_before_start' => 'Minutes before start',
        'calculate_quorum_per_college' => 'Calculate quorum per college',
        'quorum_percentage' => 'Required quorum percentage',
        'include_proxies_in_quorum' => 'Include proxies in quorum',
    ],
    'status' => [
        'ongoing' => 'Ongoing',
        'ended' => 'Ended',
        'upcoming' => 'Upcoming',
    ],
];
