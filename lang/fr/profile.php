<?php

declare(strict_types=1);

return [
    'form' => [
        'organization' => [
            'label' => 'Association',
            'required' => 'L\'association est requise.',
        ],
        'seat' => [
            'label' => 'Siège',
            'helper' => 'Sélectionnez le siège pour lequel vous souhaitez devenir délégué(e).',
            'required' => 'Le siège est requis lors de la demande pour devenir délégué(e).',
        ],
        'started_at' => [
            'label' => 'Date de début',
            'required' => 'La date de début est requise.',
        ],
        'position_title' => [
            'label' => 'Titre du poste',
            'placeholder' => 'Membre',
        ],
        'submit' => [
            'label' => 'Confirmer',
        ],
    ],

    'manage_delegates' => [
        'label' => 'Demandes de délégation',
        'add_action' => 'Ajouter une demande de délégation',
        'new_item' => 'Nouvelle demande',
        'seat_format' => '%s - %s (Siège %d)',
        'section_heading' => 'Délégation',
        'section_description' => 'Gérez vos demandes de délégation',
        'my_applications' => 'Mes demandes de délégation',
        'seat_label' => 'Siège :number',
        'submit_button' => 'Soumettre ma demande',
        'notification_title' => 'Mandat de délégué(e) en attente d\'approbation',
        'notification_body' => 'Votre mandat de délégué(e) a été soumis et est en attente d\'approbation.',
    ],

    'manage_volunteers' => [
        'label' => 'Mandats de bénévolat',
        'section_heading' => 'Bénévolat',
        'section_description' => 'Gérez vos mandats de bénévolat dans les associations',
        'my_affiliations' => 'Mes mandats actuels',
        'form_label' => 'Mandats de bénévolat',
        'add_action' => 'Ajouter un mandat',
        'new_item' => 'Nouveau mandat',
    ],

    'manage_organizations' => [
        'label' => 'Mes associations',
        'section_description' => 'Gérez vos affiliations aux associations',
        'my_affiliations' => 'Mes affiliations actuelles',
        'volunteers_heading' => 'Mandats de bénévolat',
        'delegates_heading' => 'Délégation',
        'default_position' => 'Membre',
        'seat_label' => 'Siège :number',
    ],
];
