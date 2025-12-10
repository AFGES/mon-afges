<?php

declare(strict_types=1);

return [
    'title' => 'Sélection d\'association',

    'form' => [
        'organizations' => [
            'label' => 'Associations',
            'required' => 'Vous devez ajouter au moins une association.',
            'min' => 'Vous devez ajouter au moins une association.',
            'add_action' => 'Ajouter une association',
            'new_item' => 'Nouvelle association',
        ],
        'organization' => [
            'label' => 'Association',
            'required' => 'L\'association est requise.',
        ],
        'started_at' => [
            'label' => 'Date de début',
            'required' => 'La date de début est requise.',
        ],
        'position_title' => [
            'label' => 'Titre du poste',
            'placeholder' => 'Membre',
        ],
    ],

    'actions' => [
        'submit' => [
            'label' => 'Confirmer',
        ],
    ],

    'notifications' => [
        'success' => [
            'title' => 'Associations sélectionnées',
            'body' => 'Vous êtes maintenant membre des associations sélectionnées.',
        ],
        'no_organizations' => [
            'title' => 'Aucune association disponible',
            'body' => 'Il n\'y a actuellement aucune association disponible. Veuillez contacter un administrateur.',
        ],
    ],
];
