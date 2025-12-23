<?php

// lang/fr/delegate.php

return [
    'label' => 'Délégué',
    'plural' => 'Délégués',
    'started_at' => 'À partir du',
    'ended_at' => 'Jusqu\'au',

    'relation_managers' => [
        'user_title' => 'Délégué-e',
        'organization_title' => 'Délégué-e-s',
    ],

    'fields' => [
        'seat' => 'Siège',
        'seat_helper' => 'Sélectionnez le siège pour ce délégué',
        'seat_number' => 'Siège #',
        'status' => 'Statut',
        'approved_at' => 'Approuvé le',
        'approved_by' => 'Approuvé par',
        'rejection_reason' => 'Raison du refus',
    ],

    'status' => [
        'pending' => 'En attente d\'approbation',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
    ],

    'sections' => [
        'general' => 'Informations générales',
        'approval' => 'Statut d\'approbation',
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
            'title' => 'Délégué approuvé avec succès',
        ],
        'rejected' => [
            'title' => 'Délégué refusé avec succès',
        ],
        'approval_requested' => [
            'subject' => 'Nouvelle demande d\'approbation de délégué-e',
            'greeting' => 'Bonjour :name,',
            'intro' => 'Une nouvelle demande de délégué-e requiert votre approbation.',
            'applicant' => 'Demandeur : :name',
            'organization' => 'Association : :organization',
            'seat' => 'Siège : :seat',
            'started_at' => 'Date de début : :date',
            'approve_action' => 'Approuver',
            'reject_action' => 'Refuser',
            'view_details' => 'Voir les détails : :url',
            'outro' => 'Merci de traiter cette demande dans les meilleurs délais.',
        ],
        'approval_approved' => [
            'subject' => 'Votre demande de délégué-e a été approuvée',
            'greeting' => 'Bonjour :name,',
            'intro' => 'Nous avons le plaisir de vous informer que votre demande de délégué-e a été approuvée.',
            'body' => 'Vous pouvez désormais accéder aux fonctionnalités réservées aux délégué-e-s.',
            'outro' => 'Merci de votre engagement.',
        ],
    ],
];
