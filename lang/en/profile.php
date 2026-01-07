<?php

declare(strict_types=1);

return [
    'form' => [
        'organization' => [
            'label' => 'Organization',
            'required' => 'The organization is required.',
        ],
        'email' => [
            'label' => 'Email',
            'helper' => 'Email that will be added to the Google Workspace group upon approval',
            'required' => 'The email is required.',
            'invalid' => 'The email must be a valid email address.',
        ],
        'seat' => [
            'label' => 'Seat',
            'helper' => 'Select the seat you wish to apply for as a delegate.',
            'required' => 'The seat is required when applying as a delegate.',
        ],
        'started_at' => [
            'label' => 'Start Date',
            'required' => 'The start date is required.',
        ],
        'position_title' => [
            'label' => 'Position Title',
            'placeholder' => 'Member',
        ],
        'submit' => [
            'label' => 'Confirm',
        ],
    ],

    'manage_delegates' => [
        'label' => 'Delegate Applications',
        'add_action' => 'Add Delegate Application',
        'new_item' => 'New Application',
        'seat_format' => '%s - %s (Seat %d)',
        'section_heading' => 'Delegation',
        'section_description' => 'Manage your delegate applications',
        'my_applications' => 'My Delegate Applications',
        'seat_label' => 'Seat :number',
        'submit_button' => 'Submit Application',
        'notification_title' => 'Delegate Mandate Pending Approval',
        'notification_body' => 'Your delegate mandate has been submitted and is pending approval.',
    ],

    'manage_volunteers' => [
        'label' => 'Volunteer Mandates',
        'section_heading' => 'Volunteering',
        'section_description' => 'Manage your volunteer mandates in organizations',
        'my_affiliations' => 'My Current Mandates',
        'form_label' => 'Volunteer Mandates',
        'add_action' => 'Add Mandate',
        'new_item' => 'New Mandate',
    ],

    'manage_organizations' => [
        'label' => 'My Organizations',
        'section_description' => 'Manage your organization affiliations',
        'my_affiliations' => 'My Current Affiliations',
        'volunteers_heading' => 'Volunteer Mandates',
        'delegates_heading' => 'Delegation',
        'default_position' => 'Member',
        'seat_label' => 'Seat :number',
    ],
];
