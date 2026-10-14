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
        'study_program' => 'Studies',
        'study_program_helper' => 'Program, year of study',
        'birth_date' => 'Date of birth',
        'postal_address' => 'Postal address',
        'phone' => 'Phone',
        'email' => 'Email address',
        'elected_mandates' => 'Elected mandates',
        'elected_mandates_helper' => 'University councils, faculty councils, CROUS, etc.',
        'started_at' => 'Mandate start date',
        'submitted_at' => 'Form of :date',
    ],
];
