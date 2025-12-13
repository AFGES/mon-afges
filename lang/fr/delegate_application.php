<?php

declare(strict_types=1);

return [
    'title' => 'Postuler comme délégué',
    'heading' => 'Postuler pour devenir délégué',

    'form' => [
        'organization' => [
            'label' => 'Association',
            'helper' => 'Sélectionnez l\'association pour laquelle vous souhaitez postuler comme délégué.',
            'required' => 'Veuillez sélectionner une association.',
        ],
        'seat' => [
            'label' => 'Siège',
            'helper' => 'Sélectionnez le siège pour lequel vous souhaitez postuler.',
            'required' => 'Veuillez sélectionner un siège.',
        ],
        'submit' => 'Soumettre ma candidature',
        'confirm' => [
            'heading' => 'Confirmer votre candidature',
            'description' => 'Êtes-vous sûr de vouloir soumettre votre candidature pour ce siège de délégué ?',
            'submit' => 'Confirmer',
        ],
    ],

    'notifications' => [
        'no_volunteer_relationships' => [
            'title' => 'Aucune association',
            'body' => 'Vous devez d\'abord être bénévole dans au moins une association pour postuler comme délégué.',
        ],
        'already_applied' => [
            'title' => 'Candidature existante',
            'body' => 'Vous avez déjà une candidature active pour ce siège.',
        ],
        'success' => [
            'title' => 'Candidature soumise',
            'body' => 'Votre candidature a été soumise avec succès.',
        ],
    ],

    'validation' => [
        'organization_required' => 'L\'association est requise.',
        'organization_exists' => 'L\'association sélectionnée n\'existe pas.',
        'seat_required' => 'Le siège est requis.',
        'seat_exists' => 'Le siège sélectionné n\'existe pas.',
        'must_be_volunteer' => 'Vous devez être bénévole dans cette association pour postuler comme délégué.',
    ],
];
