<?php

declare(strict_types=1);

return [
    'label' => 'Proxy',
    'plural' => 'Proxies',

    'fields' => [
        'delegator_seat' => 'Delegating seat',
        'delegator_seat_helper' => 'Seat whose holder gives the proxy',
        'proxy_holder_seat' => 'Proxy holder seat',
        'proxy_holder_seat_helper' => 'Seat whose holder receives the proxy',
        'democratic_time_session' => 'Democratic time',
        'democratic_time_session_helper' => 'Session for which this proxy is valid',
        'started_at' => 'Start',
        'started_at_helper' => 'Leave empty for a proxy valid from the start of the session',
        'ended_at' => 'End',
        'ended_at_helper' => 'Leave empty for a proxy valid until the end of the session',
    ],
];
