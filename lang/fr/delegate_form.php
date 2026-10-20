<?php

declare(strict_types=1);

// lang/fr/delegate_form.php

return [
    'title' => 'Fiche Délégué-e - Collège :college',
    'title_without_college' => 'Fiche Délégué-e',

    'navigation' => 'Fiche Délégué-e',
    'submit' => 'Envoyer ma fiche',

    'request_locked' => 'Vous avez déjà une demande en attente de validation. Vous pourrez en faire une autre une fois celle-ci traitée.',

    'confirm_replacement' => [
        'heading' => 'Remplacement de votre siège actuel',
        'description' => 'Vous occupez déjà un siège de délégué-e. Un-e délégué-e ne peut occuper qu\'un seul siège à la fois : votre siège actuel prendra fin dès que cette nouvelle demande sera approuvée.',
        'confirm' => 'Envoyer la demande',
    ],

    'notifications' => [
        'locked' => [
            'title' => 'Demande impossible',
        ],
        'submitted' => [
            'title' => 'Fiche envoyée',
            'body' => 'Votre demande a été transmise et sera examinée prochainement.',
        ],
    ],

    'sections' => [
        'seat' => 'Association',
        'general' => 'Informations générales',
        'other_mandates' => 'Autres mandats',
    ],

    'fields' => [
        'last_name' => 'Nom',
        'first_name' => 'Prénom',
        'email' => 'Adresse mail',
        'started_at' => 'Date de prise de mandat',
        'submitted_at' => 'Fiche du :date',
    ],
];
