<?php

declare(strict_types=1);

return [
    'form' => [
        'organization' => [
            'label' => 'Association',
            'required' => 'L\'association est requise.',
        ],
        'email' => [
            'helper' => 'Adresse à laquelle vous recevrez les convocations. Elle peut être différente de celle de votre compte.',
            'required' => 'Le courriel est requis.',
            'invalid' => 'Le courriel doit être une adresse courriel valide.',
        ],
        'seat' => [
            'label' => 'Siège',
            'helper' => 'Sélectionnez le siège pour lequel vous souhaitez devenir délégué-e.',
            'required' => 'Le siège est requis lors de la demande pour devenir délégué-e.',
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
        'section_heading' => 'Délégation',
        'section_description' => 'Gérez vos demandes de délégation',
        'request_action' => 'Demander un siège de délégué-e',
        'my_applications' => 'Mes demandes de délégation',
        'seat_label' => 'Siège :number',
        'ended_on' => 'Terminé le :date',
        'terminate' => [
            'action' => 'Terminer ce mandat',
            'confirm' => 'Êtes-vous sûr-e de vouloir terminer ce mandat de délégué(e) ?',
            'success_title' => 'Mandat terminé',
            'success_body' => 'Votre mandat de délégué(e) a été terminé avec succès.',
            'not_authorized_title' => 'Action non autorisée',
            'not_authorized_body' => 'Vous ne pouvez terminer que vos propres mandats approuvés et actifs.',
        ],
    ],

    'manage_volunteers' => [
        'label' => 'Mandats de bénévolat',
        'section_heading' => 'Bénévolat',
        'section_description' => 'Gérez vos mandats de bénévolat dans les associations',
        'my_affiliations' => 'Mes mandats dans des associations',
        'form_label' => 'Mandats de bénévolat',
        'add_action' => 'Ajouter un mandat',
        'new_item' => 'Nouveau mandat',
        'ended_on' => 'Terminé le :date',
        'terminate' => [
            'action' => 'Terminer ce mandat',
            'confirm' => 'Êtes-vous sûr-e de vouloir terminer ce mandat de bénévolat ?',
            'success_title' => 'Mandat terminé',
            'success_body' => 'Votre mandat de bénévolat a été terminé avec succès.',
            'not_authorized_title' => 'Action non autorisée',
            'not_authorized_body' => 'Vous ne pouvez terminer que vos propres mandats approuvés et actifs.',
        ],
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
