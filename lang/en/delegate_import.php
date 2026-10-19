<?php

declare(strict_types=1);

return [
    'action' => 'Import delegates',

    'columns' => [
        'email' => 'Email',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'phone' => 'Phone',
        'birth_date' => 'Birth date',
        'study_program' => 'Study program',
        'organization' => 'Organization',
        'college' => 'College',
        'seat_number' => 'Seat number',
        'started_at' => 'Start date',
        'ended_at' => 'End date',
    ],

    'helpers' => [
        'date' => 'Format dd/mm/yyyy or yyyy-mm-dd.',
        'organization' => 'Short name or legal name of an existing organization.',
        'names' => 'Required when the user does not exist yet.',
    ],

    'errors' => [
        'name_required_for_new_user' => 'The first and last names are required to create the user :email.',
        'organization_not_found' => 'No organization matches ":organization".',
        'organization_ambiguous' => 'Several organizations match ":organization".',
        'college_not_found' => 'No college matches ":college".',
        'college_ambiguous' => 'Several colleges match ":college".',
        'college_organization_not_found' => 'The organization :organization has no seats in the college :college.',
        'seat_not_found' => 'Seat #:seat_number does not exist for :organization in the college :college.',
    ],

    'completed' => [
        'title' => 'Delegate import completed',
        'body' => '{0} No delegate imported.|{1} 1 delegate imported.|[2,*] :count delegates imported.',
        'failed' => '{1} 1 row failed to import.|[2,*] :count rows failed to import.',
    ],
];
