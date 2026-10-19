<?php

declare(strict_types=1);

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
        'email' => 'Courriel',
        'email_helper' => 'Courriel qui sera ajouté au groupe Google Workspace lors de l\'approbation',
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

    'approval' => [
        'terminate_label' => 'Délégué-e-s actuel-le-s du siège à faire terminer',
        'occupancy' => 'Siège occupé : :count / :capacity',
        'bulk_terminate_label' => 'Mettre fin à la délégation des délégué-e-s actuel-le-s de ces sièges',
        'bulk_terminate_helper' => 'Les autres délégué-e-s en cours sur chaque siège concerné verront leur délégation se terminer à la date de début de la demande approuvée.',
        'minimum_terminations' => 'Le collège limite ce siège : au moins un-e délégué-e doit rendre son mandat.|Le collège limite ce siège : au moins :min délégué-e-s doivent rendre leur mandat.',
        'capacity_exceeded' => 'Le siège est complet pour ce collège : sélectionnez au moins un-e délégué-e qui doit rendre son mandat.|Le siège est complet pour ce collège : sélectionnez au moins :min délégué-e-s qui doivent rendre leur mandat.',
        'capacity_exceeded_on_edit' => 'Le siège est complet sur cette période : la capacité du collège est dépassée d\'un-e délégué-e.|Le siège est complet sur cette période : la capacité du collège est dépassée de :count délégué-e-s.',
        'bulk_skipped_title' => 'Demandes non approuvées',
        'bulk_skipped_body' => ':count demande non approuvée : le siège est complet. Activez la fin des délégations actuelles ou approuvez-la individuellement.|:count demandes non approuvées : leur siège est complet. Activez la fin des délégations actuelles ou approuvez-les individuellement.',
    ],

    'notifications' => [
        'approved' => [
            'title' => 'Délégué approuvé avec succès',
        ],
        'rejected' => [
            'title' => 'Délégué refusé avec succès',
        ],
    ],
];
