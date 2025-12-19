<?php

declare(strict_types=1);

return [
    'label' => 'delegate',
    'plural' => 'delegates',
    'started_at' => 'Started at',
    'ended_at' => 'Ended at',

    'fields' => [
        'seat' => 'Seat',
        'seat_helper' => 'Select the seat for this delegate',
        'seat_number' => 'Seat #',
        'status' => 'Status',
        'approved_at' => 'Approved at',
        'approved_by' => 'Approved by',
        'rejection_reason' => 'Rejection reason',
    ],

    'status' => [
        'pending' => 'Pending approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'tabs' => [
        'all' => 'All',
        'current' => 'Current',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
        'old' => 'Old',
        'future' => 'Future',
    ],

    'sections' => [
        'general' => 'General Information',
        'approval' => 'Approval Status',
    ],

    'filters' => [
        'status' => 'Status',
        'period' => 'Period',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Delegate approved successfully',
        ],
        'rejected' => [
            'title' => 'Delegate rejected successfully',
        ],
    ],
];
