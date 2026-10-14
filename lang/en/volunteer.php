<?php

declare(strict_types=1);

return [
    'label' => 'Volunteer',
    'plural_label' => 'Volunteers',

    'fields' => [
        'user' => 'User',
        'organization' => 'Organization',
        'position_title' => 'Position',
        'status' => 'Status',
        'started_at' => 'Start Date',
        'ended_at' => 'End Date',
        'approved_at' => 'Approved At',
        'approved_by' => 'Approved By',
        'rejection_reason' => 'Rejection Reason',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'deleted_at' => 'Deleted At',
    ],

    'status' => [
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'filters' => [
        'status' => 'Status',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Volunteer approved successfully',
        ],
        'rejected' => [
            'title' => 'Volunteer rejected successfully',
        ],
        'pending_approval' => [
            'title' => 'Registration pending approval',
            'body' => 'Your volunteer registration is pending approval. You will be notified once it has been reviewed.',
        ],
        'approval_requested' => [
            'subject' => 'New volunteer mandate approval request',
            'greeting' => 'Hello :name,',
            'intro' => 'A new volunteer mandate request requires your approval.',
            'applicant' => 'Applicant: :name',
            'organization' => 'Organization: :organization',
            'position' => 'Position: :position',
            'started_at' => 'Start date: :date',
            'approve_action' => 'Approve',
            'reject_action' => 'Reject',
            'view_details' => 'View details: :url',
            'outro' => 'Please process this request as soon as possible.',
        ],
        'approval_approved' => [
            'subject' => 'Your volunteer mandate application has been approved',
            'greeting' => 'Hello :name,',
            'intro' => 'We are pleased to inform you that your volunteer mandate application has been approved.',
            'body' => 'You can now access the features reserved for volunteers.',
            'outro' => 'Thank you for your commitment.',
        ],
    ],

    'stats' => [
        'pending_approvals' => 'Pending Approvals',
        'pending_description' => 'Volunteers awaiting approval',
    ],

    'sections' => [
        'general' => 'General Information',
        'approval' => 'Approval Status',
        'metadata' => 'Metadata',
    ],

    'placeholders' => [
        'no_position' => 'No position specified',
        'ongoing' => 'Ongoing',
    ],
];
