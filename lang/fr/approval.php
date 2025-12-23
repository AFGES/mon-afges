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
        'rejected_at' => 'Refusé le',
        'rejected_by' => 'Refusé par',
        'rejection_reason' => 'Raison du refus',
    ],

    'actions' => [
        'approve' => 'Approuver',
        'reject' => 'Refuser',
    ],

    'modal' => [
        'approve' => [
            'heading' => 'Approuver la demande',
            'description' => 'Êtes-vous sûr-e de vouloir approuver cette demande ? Cette action est irréversible.',
        ],
        'reject' => [
            'heading' => 'Refuser la demande',
            'description' => 'Êtes-vous sûr-e de vouloir refuser cette demande ? Veuillez indiquer la raison du refus.',
        ],
        'bulk_approve' => [
            'heading' => 'Approuver les demandes sélectionnées',
            'description' => 'Êtes-vous sûr-e de vouloir approuver toutes les demandes sélectionnées ? Cette action est irréversible.',
        ],
        'bulk_reject' => [
            'heading' => 'Refuser les demandes sélectionnées',
            'description' => 'Êtes-vous sûr-e de vouloir refuser toutes les demandes sélectionnées ? Veuillez indiquer la raison du refus.',
        ],
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Approuvé avec succès',
            'body' => 'La demande a été approuvée.',
        ],
        'rejected' => [
            'title' => 'Refusé avec succès',
            'body' => 'La demande a été refusée.',
        ],
        'bulk_approved' => [
            'title' => 'Approuvé avec succès',
            'body' => ':count demande(s) ont été approuvée(s).',
        ],
        'bulk_rejected' => [
            'title' => 'Refusé avec succès',
            'body' => ':count demande(s) ont été refusée(s).',
        ],
        'pending' => [
            'title' => 'En attente d\'approbation',
            'body' => 'Votre demande est en attente d\'approbation. Vous serez notifié-e une fois qu\'elle aura été examinée.',
        ],
    ],
];
