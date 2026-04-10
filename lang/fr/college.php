<?php

declare(strict_types=1);

return [
    'label' => 'Collège',
    'plural' => 'Collèges',

    'fields' => [
        'name' => 'Nom',
        'can_vote' => 'Peut voter',
        'max_seats_per_organization' => 'Sièges max par association',
        'max_delegates_per_seat' => 'Délégués max par siège',
        'require_same_college_for_proxy' => 'Procuration limitée au même collège',
        'delegates_count' => 'Nombre de délégués',
        'seats' => 'Sièges',
        'delegates_per_seat' => 'Délégués par siège',
        'total_positions' => 'Postes totaux',
        'created_at' => 'Créé le',
        'updated_at' => 'Mis à jour le',
    ],

    'helpers' => [
        'max_seats_per_organization' => 'Nombre maximum de sièges qu\'une association peut avoir dans ce collège',
        'max_delegates_per_seat' => 'Nombre maximum de délégués pouvant occuper chaque siège dans ce collège',
        'require_same_college_for_proxy' => 'Si activé, les procurations doivent être données à un siège du même collège. Si désactivé, les procurations peuvent être données à n\'importe quel siège d\'un collège délibérant.',
    ],

    'colleges' => [
        'general' => 'Général',
        'federal' => 'Fédéral',
        'advisory' => 'Consultatif',
    ],
];
