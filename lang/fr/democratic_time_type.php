<?php

declare(strict_types=1);

return [
    'label' => 'Type de temps démocratique',
    'plural' => 'Types de temps démocratique',
    'fields' => [
        'name' => 'Nom',
        'acronym' => 'Acronyme',
        'convocation_interval_days' => 'Délai de convocation (jours)',
        'calculate_quorum_per_college' => 'Calculer le quorum par collège',
        'quorum_percentage' => 'Pourcentage requis pour le quorum',
        'sessions_count' => 'Nombre de séances',
        'include_proxies_in_quorum' => 'Inclure les procurations dans le quorum',
    ],
    'suffixes' => [
        'days' => 'jours',
    ],
    'sections' => [
        'quorum_configuration' => 'Configuration du quorum',
    ],
    'help' => [
        'calculate_quorum_per_college' => 'Si activé, le quorum doit être atteint par chaque collège. Sinon, le quorum est calculé sur l\'ensemble des collèges',
        'quorum_percentage' => 'Pourcentage de délégué-e-s présent-e-s requis pour atteindre le quorum',
        'include_proxies_in_quorum' => 'Si activé, les procurations comptent dans le calcul du quorum',
    ],
];
