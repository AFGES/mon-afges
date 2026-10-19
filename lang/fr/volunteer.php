<?php

declare(strict_types=1);

return [
    'label' => 'Mandat',
    'plural_label' => 'Mandats',

    'relation_managers' => [
        'user_title' => 'Mandats',
        'organization_title' => 'Bénévoles',
    ],

    'fields' => [
        'user' => 'Utilisateur',
        'organization' => 'Association',
        'position_title' => 'Poste',
        'status' => 'Statut',
        'started_at' => 'Date de début',
        'ended_at' => 'Date de fin',
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
        'period' => 'Période',
        'current' => 'Actuel',
        'pending' => 'En attente',
        'rejected' => 'Refusés',
        'old' => 'Ancien',
        'future' => 'Futur',
        'all' => 'Tout',
    ],

    'actions' => [
        'approve' => 'Approuver',
        'reject' => 'Refuser',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Mandat de bénévolat approuvé avec succès',
        ],
        'rejected' => [
            'title' => 'Mandat de bénévolat refusé avec succès',
        ],
        'pending_approval' => [
            'title' => 'Candidature en attente d\'approbation',
            'body' => 'Votre candidature pour un mandat de bénévolat est en attente d\'approbation. Vous serez notifié(e) une fois qu\'elle aura été examinée.',
        ],
    ],

    'stats' => [
        'pending_approvals' => 'Approbations en attente',
        'pending_description' => 'Mandats de bénévolat en attente d\'approbation',
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
