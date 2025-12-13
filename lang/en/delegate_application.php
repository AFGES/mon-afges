<?php

declare(strict_types=1);

return [
    'title' => 'Apply as Delegate',
    'heading' => 'Apply to become a delegate',

    'form' => [
        'organization' => [
            'label' => 'Association',
            'helper' => 'Select the association you wish to apply for as a delegate.',
            'required' => 'Please select an association.',
        ],
        'seat' => [
            'label' => 'Seat',
            'helper' => 'Select the seat you wish to apply for.',
            'required' => 'Please select a seat.',
        ],
        'submit' => 'Submit my application',
        'confirm' => [
            'heading' => 'Confirm your application',
            'description' => 'Are you sure you want to submit your application for this delegate seat?',
            'submit' => 'Confirm',
        ],
    ],

    'notifications' => [
        'no_volunteer_relationships' => [
            'title' => 'No associations',
            'body' => 'You must first be a volunteer in at least one association to apply as a delegate.',
        ],
        'already_applied' => [
            'title' => 'Existing application',
            'body' => 'You already have an active application for this seat.',
        ],
        'success' => [
            'title' => 'Application submitted',
            'body' => 'Your application has been submitted successfully.',
        ],
    ],

    'validation' => [
        'organization_required' => 'The association is required.',
        'organization_exists' => 'The selected association does not exist.',
        'seat_required' => 'The seat is required.',
        'seat_exists' => 'The selected seat does not exist.',
        'must_be_volunteer' => 'You must be a volunteer in this association to apply as a delegate.',
    ],
];
