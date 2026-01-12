<?php

declare(strict_types=1);

return [
    'label' => 'émargement',
    'plural' => 'émargements',

    'fields' => [
        'seat' => 'siège',
        'delegate' => 'délégué-e',
        'signed_in_at' => 'signé-e à',
        'signed_out_at' => 'signé-e sortie à',
        'signed_in_by_seat' => 'signé-e par (procuration)',
        'is_signed_in' => 'actuellement présent-e',
        'signed_out_early' => 'parti-e avant la fin',
        'is_proxy_sign_in' => 'signé-e via procuration',
    ],

    'help' => [
        'signed_in_by_seat' => 'Laisser vide si le-a délégué-e signe pour lui-elle-même',
        'delegate' => 'Sélectionnez d\'abord un siège pour voir les délégué-e-s',
    ],

    'sections' => [
        'sign_in_details' => 'Détails d\'émargement',
        'sign_in_management' => 'Gestion de l\'émargement',
    ],

    'status' => [
        'open' => 'ouvert',
        'closed' => 'fermé',
        'not_opened' => 'pas encore ouvert',
    ],

    'filters' => [
        'currently_signed_in' => 'Actuellement présent-e-s',
        'signed_out' => 'Parti-e-s',
    ],

    'actions' => [
        'open_sign_in' => 'Ouvrir l\'émargement',
        'close_sign_in' => 'Fermer l\'émargement',
        'sign_in' => 'Émarger',
        'sign_out' => 'Signer la sortie',
        'sign_in_delegate' => 'Émarger un-e délégué-e',
        'sign_out_delegate' => 'Signer la sortie d\'un-e délégué-e',
        'view_sign_ins' => 'Voir les émargements',
    ],

    'messages' => [
        'sign_in_opened' => 'L\'émargement a été ouvert.',
        'sign_in_closed' => 'L\'émargement a été fermé.',
        'sign_in_already_opened' => 'L\'émargement est déjà ouvert.',
        'sign_in_already_closed' => 'L\'émargement est déjà fermé.',
        'signed_in_successfully' => 'Émargement enregistré avec succès.',
        'signed_out_successfully' => 'Sortie enregistrée avec succès.',
        'already_signed_in' => 'Ce siège a déjà émargé pour cette session.',
        'not_signed_in' => 'Ce siège n\'a pas encore émargé pour cette session.',
        'sign_in_not_open' => 'L\'émargement n\'est pas encore ouvert pour cette session.',
        'sign_in_closed_message' => 'L\'émargement est fermé pour cette session.',
    ],

    'help' => [
        'auto_open' => 'Ouvrir automatiquement l\'émargement X minutes avant le début',
        'minutes_before_start' => 'Nombre de minutes avant le début pour ouvrir automatiquement',
        'sign_in_opens_at' => 'Date et heure d\'ouverture de l\'émargement',
        'sign_in_closes_at' => 'Date et heure de fermeture de l\'émargement',
    ],
];
