<?php

declare(strict_types=1);

// lang/fr/organization.php

return [
    'label' => 'Association',
    'plural' => 'Associations',

    'fields' => [
        'short_name' => 'Nom court',
        'legal_name' => 'Raison sociale',
        'domain' => 'Nom de domaine',
        'total_seats' => 'Total des sièges',
        'total_delegates' => 'Total des délégués',
        'created_at' => 'Créé le',
        'updated_at' => 'Mis à jour le',
        'deleted_at' => 'Supprimé le',
    ],

    'sections' => [
        'details' => 'Détails de l\'association',
        'college_associations' => 'Associations aux collèges',
        'college_associations_description' => 'Gérer les allocations de sièges pour cette association dans les différents collèges',
    ],

    'repeater' => [
        'label' => 'Allocations de sièges par collège',
        'add_action' => 'Ajouter une allocation de collège',
        'new_allocation' => 'Nouvelle allocation',
    ],

    'helpers' => [
        'domain' => 'Le nom de domaine de l\'association (utiliser pour l\'affectation automatique des bénévoles, par exemple: afges.org)',
        'seats' => 'Nombre total de sièges alloués à cette association dans ce collège',
        'delegates_per_seat' => 'Nombre de délégués pouvant occuper chaque siège',
    ],

    'notifications' => [
        'cannot_remove_seats' => [
            'title' => 'Impossible de supprimer les sièges',
            'body' => 'Le collège :college a seulement :empty siège(s) vide(s), mais vous avez demandé d\'en supprimer :requested. Libérez d\'abord les sièges occupés.',
        ],
        'seats_updated' => [
            'title' => 'Sièges mis à jour',
            'body' => 'Les allocations de sièges ont été mises à jour avec succès.',
        ],
        'seats_update_failed' => [
            'title' => 'Échec de la mise à jour',
        ],
    ],
];
