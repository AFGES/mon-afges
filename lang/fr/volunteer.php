<?php

declare(strict_types=1);

return [
    'label' => 'Bénévole',
    'plural_label' => 'Bénévoles',

    'fields' => [
        'user' => 'Utilisateur',
        'organization' => 'Association',
        'position_title' => 'Poste',
        'status' => 'Statut',
        'started_at' => 'Début',
        'ended_at' => 'Fin',
        'approved_at' => 'Approuvé le',
        'approved_by' => 'Approuvé par',
        'rejection_reason' => 'Raison du refus',
        'created_at' => 'Créé le',
        'updated_at' => 'Modifié le',
        'deleted_at' => 'Supprimé le',
    ],

    'status' => [
        'pending' => 'En attente d\'approbation',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
    ],

    'filters' => [
        'status' => 'Statut',
    ],

    'actions' => [
        'approve' => 'Approuver',
        'reject' => 'Refuser',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Bénévole approuvé avec succès',
        ],
        'rejected' => [
            'title' => 'Bénévole refusé avec succès',
        ],
        'pending_approval' => [
            'title' => 'Inscription en attente d\'approbation',
            'body' => 'Votre inscription en tant que bénévole est en attente d\'approbation. Vous serez notifié une fois qu\'elle aura été examinée.',
        ],
    ],

    'stats' => [
        'pending_approvals' => 'Approbations en attente',
        'pending_description' => 'Bénévoles en attente d\'approbation',
    ],

    'sections' => [
        'general' => 'Informations générales',
        'approval' => 'Statut d\'approbation',
        'metadata' => 'Métadonnées',
    ],

    'placeholders' => [
        'no_position' => 'Aucun poste spécifié',
        'ongoing' => 'En cours',
    ],
];
