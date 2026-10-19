<?php

declare(strict_types=1);

return [
    'form' => [
        'organization' => [
            'label' => 'Organization',
            'required' => 'The organization is required.',
        ],
        'email' => [
            'helper' => 'Address where you will receive notices of meeting. It may differ from your account email.',
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
            'required' => 'The position title is required.',
        ],
        'submit' => [
            'label' => 'Confirm',
        ],
    ],

    'manage_delegates' => [
        'label' => 'Delegate Applications',
        'section_heading' => 'Delegation',
        'section_description' => 'Manage your delegate applications',
        'request_action' => 'Request a delegate seat',
        'my_applications' => 'My Delegate Applications',
        'seat_label' => 'Seat :number',
        'ended_on' => 'Ended on :date',
        'terminate' => [
            'action' => 'End this mandate',
            'confirm' => 'Are you sure you want to end this delegate mandate?',
            'success_title' => 'Mandate ended',
            'success_body' => 'Your delegate mandate has been ended.',
            'not_authorized_title' => 'Action not allowed',
            'not_authorized_body' => 'You can only end your own approved and active mandates.',
        ],
    ],

    'manage_volunteers' => [
        'label' => 'Volunteer Mandates',
        'section_heading' => 'Volunteering',
        'section_description' => 'Manage your volunteer mandates in organizations',
        'my_affiliations' => 'My Current Mandates',
        'form_label' => 'Volunteer Mandates',
        'add_action' => 'Add Mandate',
        'new_item' => 'New Mandate',
        'request_locked' => [
            'message' => 'Your request for :organization is awaiting approval. You cannot request another organization until it is processed.',
            'single_new_organization' => 'You can only request one new organization at a time.',
        ],
        'position_title_required' => [
            'banner' => 'You were linked to an organization through your email address. Define your position title to access the application.',
            'field_label' => 'Position title (:organization)',
            'success_title' => 'Position title saved',
        ],
    ],

    'manage_passkeys' => [
        'reauthenticate' => [
            'body' => 'For security reasons, log in again to manage your passkeys.',
            'action' => 'Log out',
            'confirm_action' => 'Confirm with a passkey',
            'confirm_failed' => 'Passkey confirmation failed.',
        ],
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
