<?php

declare(strict_types=1);

return [
    'label' => 'Séance de temps démocratique',
    'plural' => 'Séances de temps démocratique',
    'fields' => [
        'democratic_time_type' => 'Type de temps démocratique',
        'started_at' => 'Date et heure de début',
        'ended_at' => 'Date et heure de fin',
        'location' => 'Lieu',
        'sign_in_opens_at' => 'Ouverture de l\'émargement',
        'sign_in_closes_at' => 'Fermeture de l\'émargement',
        'auto_open_sign_in' => 'Ouverture automatique',
        'minutes_before_start' => 'Minutes avant le début',
        'calculate_quorum_per_college' => 'Calculer le quorum par collège',
        'quorum_percentage' => 'Pourcentage requis pour le quorum',
        'include_proxies_in_quorum' => 'Inclure les procurations dans le quorum',
    ],
    'status' => [
        'ongoing' => 'En cours',
        'ended' => 'Terminée',
        'upcoming' => 'À venir',
    ],
    'sections' => [
        'quorum_configuration' => 'Configuration du quorum (copiée du type)',
    ],
    'help' => [
        'quorum_preserved' => 'Ces valeurs ont été copiées du type de temps démocratique lors de la création de la séance et ne changent pas si le type est modifié',
    ],
];
