<?php

declare(strict_types=1);

return [
    'title' => 'Organization Selection',

    'form' => [
        'organizations' => [
            'label' => 'Organizations',
            'required' => 'You must add at least one organization.',
            'min' => 'You must add at least one organization.',
            'add_action' => 'Add an organization',
            'new_item' => 'New organization',
        ],
        'organization' => [
            'label' => 'Organization',
            'required' => 'The organization is required.',
        ],
        'started_at' => [
            'label' => 'Start date',
            'required' => 'The start date is required.',
        ],
        'position_title' => [
            'label' => 'Position title',
            'placeholder' => 'Member',
        ],
    ],

    'actions' => [
        'submit' => [
            'label' => 'Confirm',
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Organizations selected',
            'body' => 'You are now a member of the selected organizations.',
        ],
        'no_organizations' => [
            'title' => 'No organizations available',
            'body' => 'There are currently no organizations available. Please contact an administrator.',
        ],
    ],
];
