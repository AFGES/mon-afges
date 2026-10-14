<?php

declare(strict_types=1);

// lang/fr/delegate_form.php

return [
    'title' => 'Fiche Délégué-e - Collège :college',
    'title_without_college' => 'Fiche Délégué-e',

    'navigation' => 'Fiche Délégué-e',
    'submit' => 'Envoyer ma fiche',

    'widget' => [
        'heading' => 'Devenir délégué-e',
        'description' => 'Remplissez votre fiche pour demander un siège de délégué-e au nom de votre association.',
        'action' => 'Remplir ma fiche',
        'pending' => '{1} Vous avez :count demande en attente de validation.|[2,*] Vous avez :count demandes en attente de validation.',
    ],

    'notifications' => [
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
        'study_program' => 'Cursus',
        'study_program_helper' => 'Filière, année d\'études',
        'birth_date' => 'Date de naissance',
        'postal_address' => 'Adresse postale',
        'phone' => 'Téléphone',
        'email' => 'Adresse mail',
        'elected_mandates' => 'Mandats d\'élu-e',
        'elected_mandates_helper' => 'Conseils centraux, conseils de composante, CROUS, etc.',
        'started_at' => 'Date de prise de mandat',
        'submitted_at' => 'Fiche du :date',
    ],
];
