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
        'seat' => [
            'label' => 'Siège',
            'helper' => 'Sélectionnez le siège pour lequel vous souhaitez devenir délégué-e.',
            'required' => 'Le siège est requis lors de la demande pour devenir délégué-e.',
        ],
    ],

    'actions' => [
        'submit' => [
            'label' => 'Confirmer',
        ],
    ],

    'notifications' => [
        'volunteer_submitted' => [
            'title' => 'Mandats en attente d\'approbation',
            'body' => 'Vos demandes de mandats ont été soumises et sont en attente d\'approbation.',
        ],
        'delegate_submitted' => [
            'title' => 'Mandat de délégué-e en attente d\'approbation',
            'body' => 'Votre mandat de délégué-e a été soumis et est en attente d\'approbation.',
        ],
    ],
];
