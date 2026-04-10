<?php

declare(strict_types=1);

// lang/en/organization.php

return [
    'label' => 'organization',
    'plural' => 'organizations',

    'fields' => [
        'short_name' => 'Short Name',
        'legal_name' => 'Legal Name',
        'domain' => 'Domain Name',
        'total_seats' => 'Total Seats',
        'total_delegates' => 'Total Delegates',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
    ],

    'sections' => [
        'details' => 'Organization Details',
        'college_associations' => 'College Associations',
        'college_associations_description' => 'Manage seat allocations for this organization across different colleges',
    ],

    'repeater' => [
        'label' => 'Seat Allocations by College',
        'add_action' => 'Add College Allocation',
        'new_allocation' => 'New Allocation',
    ],

    'helpers' => [
        'domain' => 'The organization\'s domain name (e.g., afges.org)',
        'seats' => 'Total number of seats allocated to this organization in this college',
        'delegates_per_seat' => 'Number of delegates that can occupy each seat',
    ],

    'notifications' => [
        'cannot_remove_seats' => [
            'title' => 'Cannot Remove Seats',
            'body' => 'College :college has only :empty empty seat(s), but you requested to remove :requested. Free occupied seats first.',
        ],
        'seats_updated' => [
            'title' => 'Seats Updated',
            'body' => 'Seat allocations have been successfully updated.',
        ],
        'seats_update_failed' => [
            'title' => 'Update Failed',
        ],
    ],
];
