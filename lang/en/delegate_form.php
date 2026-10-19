<?php

declare(strict_types=1);

// lang/en/delegate_form.php

return [
    'title' => 'Delegate form - :college college',
    'title_without_college' => 'Delegate form',

    'navigation' => 'Delegate form',
    'submit' => 'Submit my form',

    'widget' => [
        'heading' => 'Become a delegate',
        'description' => 'Fill in your form to request a delegate seat on behalf of your association.',
        'action' => 'Fill in my form',
        'pending' => '{1} You have :count request awaiting approval.|[2,*] You have :count requests awaiting approval.',
    ],

    'notifications' => [
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
        'started_at' => 'Mandate start date',
        'submitted_at' => 'Form of :date',
    ],
];
