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
        'approval_requested' => [
            'subject' => 'New delegate approval request',
            'greeting' => 'Hello :name,',
            'intro' => 'A new delegate request requires your approval.',
            'applicant' => 'Applicant: :name',
            'organization' => 'Organization: :organization',
            'seat' => 'Seat: :seat',
            'started_at' => 'Start date: :date',
            'approve_action' => 'Approve',
            'reject_action' => 'Reject',
            'view_details' => 'View details: :url',
            'outro' => 'Please process this request as soon as possible.',
        ],
        'approval_approved' => [
            'subject' => 'Your delegate request has been approved',
            'greeting' => 'Hello :name,',
            'intro' => 'We are pleased to inform you that your delegate request has been approved.',
            'body' => 'You can now access the features reserved for delegates.',
            'outro' => 'Thank you for your commitment.',
        ],
    ],
];
