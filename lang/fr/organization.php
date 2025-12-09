<?php

// lang/fr/organization.php

return [
    'label' => 'association',
    'plural' => 'associations',

    'fields' => [
        'short_name' => 'Nom court',
        'legal_name' => 'Raison sociale',
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
        'seats' => 'Nombre total de sièges alloués à cette association dans ce collège',
        'delegates_per_seat' => 'Nombre de délégués pouvant occuper chaque siège',
    ],
];
