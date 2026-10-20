<?php

declare(strict_types=1);

return [
    'heading' => 'Become a delegate',
    'description' => 'Fill in your form to request a delegate seat on behalf of your association.',
    'action' => 'Fill in my form',
    'pending' => '{1} You have :count request awaiting approval.|[2,*] You have :count requests awaiting approval.',

    'seat_holder' => [
        'heading' => 'Your delegate seat',
        'description' => 'You currently hold the following delegate seat.',
        'replacement_notice' => 'A delegate holds one seat at a time: a new request ends your current seat once approved.',
        'action' => 'Request a seat change',
        'since' => 'Since :date',
        'starts_on' => 'Starts on :date',
    ],
];
