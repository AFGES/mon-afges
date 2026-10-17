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

    'approval' => [
        'terminate_label' => 'Current delegates of the seat to end',
        'occupancy' => 'Seat occupancy: :count / :capacity',
        'bulk_terminate_label' => 'End the delegation of the current delegates of these seats',
        'bulk_terminate_helper' => 'Other current delegates of each concerned seat will end on the start date of the approved request.',
        'minimum_terminations' => 'The college limits this seat: at least one delegate must give up their mandate.|The college limits this seat: at least :min delegates must give up their mandate.',
        'capacity_exceeded' => 'The seat is full for this college: select at least one delegate who must give up their mandate.|The seat is full for this college: select at least :min delegates who must give up their mandate.',
        'capacity_exceeded_on_edit' => 'The seat is full for these dates: this delegate\'s period exceeds the college capacity by :count delegate.|The seat is full for these dates: this delegate\'s period exceeds the college capacity by :count delegates.',
        'bulk_skipped_title' => 'Requests not approved',
        'bulk_skipped_body' => ':count request was not approved because its seat is full. Enable ending current delegations or approve it individually.|:count requests were not approved because their seat is full. Enable ending current delegations or approve them individually.',
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
