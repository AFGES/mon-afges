<?php

// lang/fr/delegate.php

return [
    'label' => 'délégué',
    'plural' => 'délégués',
    'started_at' => 'À partir du',
    'ended_at' => 'Jusqu\'au',

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

    'tabs' => [
        'current' => 'Actuel',
        'pending' => 'En attente',
        'rejected' => 'Refusés',
        'old' => 'Ancien',
        'future' => 'Futur',
        'all' => 'Tout',
    ],

    'sections' => [
        'general' => 'Informations générales',
        'approval' => 'Statut d\'approbation',
    ],

    'filters' => [
        'status' => 'Statut',
        'period' => 'Période',
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
    ],
];
