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
        'apply_as_delegate' => [
            'label' => 'Postuler comme délégué',
            'helper' => 'Cochez cette option si vous souhaitez également postuler pour devenir délégué pour cette association.',
        ],
        'seat' => [
            'label' => 'Siège',
            'helper' => 'Sélectionnez le siège pour lequel vous souhaitez postuler en tant que délégué.',
            'required' => 'Le siège est requis lors de la candidature en tant que délégué.',
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
        'volunteer_and_delegate_submitted' => [
            'title' => 'Candidatures soumises',
            'body' => 'Vos candidatures de bénévole et de délégué ont été soumises et sont en attente d\'approbation.',
        ],
        'delegate_submitted' => [
            'title' => 'Candidatures de délégué soumises',
            'body' => 'Vos candidatures de délégué ont été soumises et sont en attente d\'approbation.',
        ],
    ],
];
