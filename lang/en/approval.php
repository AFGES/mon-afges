<?php

declare(strict_types=1);

return [
    'status' => [
        'pending' => 'Pending approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'fields' => [
        'status' => 'Status',
        'approved_at' => 'Approved at',
        'approved_by' => 'Approved by',
        'rejection_reason' => 'Rejection reason',
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Successfully approved',
        ],
        'rejected' => [
            'title' => 'Successfully rejected',
        ],
        'pending' => [
            'title' => 'Pending approval',
            'body' => 'Your request is pending approval. You will be notified once it has been reviewed.',
        ],
    ],
];
