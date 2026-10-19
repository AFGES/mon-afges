<?php

declare(strict_types=1);

// lang/fr/proxy.php

return [
    'label' => 'Procuration',
    'plural' => 'Procurations',

    'fields' => [
        'delegator_seat' => 'Siège délégateur',
        'delegator_seat_helper' => 'Siège dont le titulaire donne sa procuration',
        'proxy_holder_seat' => 'Siège mandataire',
        'proxy_holder_seat_helper' => 'Siège dont le titulaire reçoit la procuration',
        'democratic_time_session' => 'Temps démocratique',
        'democratic_time_session_helper' => 'Session pour laquelle cette procuration est valide',
        'started_at' => 'Début',
        'started_at_helper' => 'Laissez vide pour une procuration valide dès le début de la session',
        'ended_at' => 'Fin',
        'ended_at_helper' => 'Laissez vide pour une procuration valide jusqu\'à la fin de la session',
        'period' => 'Période',
        'active_status' => 'Statut',
    ],

    'sections' => [
        'seats' => 'Sièges',
        'session' => 'Session',
        'period' => 'Période de validité',
    ],

    'filters' => [
        'session' => 'Session',
        'active' => 'Actives',
        'inactive' => 'Inactives',
        'all' => 'Toutes',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'not_started' => 'Pas encore commencée',
        'ended' => 'Terminée',
    ],

    'navigation' => [
        'group' => 'Temps démocratique',
    ],

    'validation' => [
        'same_college' => 'Le siège mandataire doit appartenir au même collège que le siège délégateur.',
        'different_seats' => 'Le siège mandataire doit être différent du siège délégateur.',
    ],
];
