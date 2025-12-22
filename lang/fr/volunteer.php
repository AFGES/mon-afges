<?php

declare(strict_types=1);

return [
    'label' => 'Mandat de bénévolat',
    'plural_label' => 'Mandats de bénévolat',

    'fields' => [
        'user' => 'Utilisateur',
        'organization' => 'Association',
        'position_title' => 'Titre du poste',
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
        'approval_requested' => [
            'subject' => 'Nouvelle demande d\'approbation de mandat de bénévolat',
            'greeting' => 'Bonjour :name,',
            'intro' => 'Une nouvelle demande de mandat de bénévolat requiert votre approbation.',
            'applicant' => 'Demandeur : :name',
            'organization' => 'Association : :organization',
            'position' => 'Poste : :position',
            'started_at' => 'Date de début : :date',
            'approve_action' => 'Approuver',
            'reject_action' => 'Refuser',
            'view_details' => 'Voir les détails : :url',
            'outro' => 'Merci de traiter cette demande dans les meilleurs délais.',
        ],
        'approval_approved' => [
            'subject' => 'Votre candidature au mandat de bénévolat a été approuvée',
            'greeting' => 'Bonjour :name,',
            'intro' => 'Nous avons le plaisir de vous informer que votre candidature au mandat de bénévolat a été approuvée.',
            'body' => 'Vous pouvez désormais accéder aux fonctionnalités réservées aux bénévoles.',
            'outro' => 'Merci de votre engagement.',
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
