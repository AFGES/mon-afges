<?php

declare(strict_types=1);

return [
    'status' => [
        'pending' => 'En attente d\'approbation',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
    ],

    'fields' => [
        'status' => 'Statut',
        'approved_at' => 'Approuvé le',
        'approved_by' => 'Approuvé par',
        'rejection_reason' => 'Raison du refus',
    ],

    'actions' => [
        'approve' => 'Approuver',
        'reject' => 'Refuser',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Approuvé avec succès',
        ],
        'rejected' => [
            'title' => 'Refusé avec succès',
        ],
        'pending' => [
            'title' => 'En attente d\'approbation',
            'body' => 'Votre demande est en attente d\'approbation. Vous serez notifié une fois qu\'elle aura été examinée.',
        ],
    ],
];
