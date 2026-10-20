<?php

declare(strict_types=1);

// lang/en/delegate_form.php

return [
    'title' => 'Delegate form - :college college',
    'title_without_college' => 'Delegate form',

    'navigation' => 'Delegate form',
    'submit' => 'Submit my form',

    'request_locked' => 'You already have a request awaiting validation. You can submit another one once it has been processed.',
    'organization_not_eligible' => 'You are not eligible to request a seat for this organization.',

    'confirm_replacement' => [
        'heading' => 'Replace your current seat',
        'description' => 'You already hold a delegate seat. A delegate can only hold one seat at a time: your current seat will end as soon as this new request is approved.',
        'confirm' => 'Submit request',
    ],

    'notifications' => [
        'locked' => [
            'title' => 'Request not sent',
        ],
        'submitted' => [
            'title' => 'Form submitted',
            'body' => 'Your request has been submitted and will be reviewed shortly.',
        ],
    ],

    'sections' => [
        'seat' => 'Organization',
        'general' => 'General information',
        'other_mandates' => 'Other mandates',
    ],

    'fields' => [
        'last_name' => 'Last name',
        'first_name' => 'First name',
        'email' => 'Email address',
        'started_at' => 'Mandate start date and time',
        'submitted_at' => 'Form of :date',
    ],
];
