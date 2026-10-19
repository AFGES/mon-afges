<?php

declare(strict_types=1);

return [
    'action' => 'Importer des délégués',

    'columns' => [
        'email' => 'Email',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'phone' => 'Téléphone',
        'birth_date' => 'Date de naissance',
        'study_program' => 'Cursus',
        'organization' => 'Association',
        'college' => 'Collège',
        'seat_number' => 'Numéro de siège',
        'started_at' => 'Date de début',
        'ended_at' => 'Date de fin',
    ],

    'helpers' => [
        'date' => 'Format jj/mm/aaaa ou aaaa-mm-jj.',
        'organization' => 'Nom court ou raison sociale d\'une association existante.',
        'names' => 'Obligatoire lorsque l\'utilisateur n\'existe pas encore.',
    ],

    'errors' => [
        'name_required_for_new_user' => 'Le prénom et le nom sont obligatoires pour créer l\'utilisateur :email.',
        'organization_not_found' => 'Aucune association ne correspond à « :organization ».',
        'organization_ambiguous' => 'Plusieurs associations correspondent à « :organization ».',
        'college_not_found' => 'Aucun collège ne correspond à « :college ».',
        'college_ambiguous' => 'Plusieurs collèges correspondent à « :college ».',
        'college_organization_not_found' => 'L\'association :organization n\'a aucun siège dans le collège :college.',
        'seat_not_found' => 'Le siège n°:seat_number n\'existe pas pour :organization dans le collège :college.',
    ],

    'completed' => [
        'title' => 'Import des délégués terminé',
        'body' => '{0} Aucun délégué importé.|{1} 1 délégué importé.|[2,*] :count délégués importés.',
        'failed' => '{1} 1 ligne n\'a pas pu être importée.|[2,*] :count lignes n\'ont pas pu être importées.',
    ],
];
