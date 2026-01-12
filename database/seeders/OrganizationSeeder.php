<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

final class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            ['short_name' => 'AFGES', 'legal_name' => 'AFGES, les Étudiant-e-s d’Alsace', 'domain' => 'afges.org'],
            ['short_name' => 'BDS', 'legal_name' => 'Bureau des Sports de l\'Université de Strasbourg'],
            ['short_name' => 'BDA', 'legal_name' => 'Bureau des Arts de l\'Université de Strasbourg'],
            ['short_name' => 'BDE', 'legal_name' => 'Bureau des Élèves de l\'Université de Strasbourg'],
            ['short_name' => 'CROUS', 'legal_name' => 'Centre Régional des Œuvres Universitaires et Scolaires de Strasbourg'],
            ['short_name' => 'AEIS', 'legal_name' => 'Association des Étudiants en Informatique de Strasbourg'],
            ['short_name' => 'AEPS', 'legal_name' => 'Association des Étudiants en Pharmacie de Strasbourg'],
            ['short_name' => 'CEPhI', 'legal_name' => 'Comité des Etudiants en Pharmacie Industrielle de Strasbourg'],
            ['short_name' => 'OHJS', 'legal_name' => 'Orchestre d\'Harmonie des Jeunes de Strasbourg'],
            ['short_name' => 'AEKS', 'legal_name' => 'Amicale des Etudiants en Kinésithérapie de Strasbourg'],
            ['short_name' => 'T2S', 'legal_name' => 'Tutorat Santé Strasbourg'],
            ['short_name' => 'HCS', 'legal_name' => 'Humani\'Care Strasbourg'],
            ['short_name' => 'AEGS', 'legal_name' => 'Association des Etudiants en Géographie de Strasbourg'],
            ['short_name' => 'AESIS', 'legal_name' => 'Association des Étudiant.e.s en Sciences Infirmières
de Strasbourg'],
            ['short_name' => 'OUS', 'legal_name' => 'Orchestre Universitaire de Starsbourg'],
            ['short_name' => 'AIUS', 'legal_name' => 'Amicale des Informaticien-ne-s de l’Université de Strasbourg'],
            ['short_name' => 'CORE', 'legal_name' => 'CORE - Association Amicale des Étudiant-e-s et Ancien-ne-s Étudiant-e-s du Département Informatique de l’Institut Universitaire de Technologie Robert Schuman'],
            ['short_name' => 'DAG', 'legal_name' => 'Dionysos à Galien'],
            ['short_name' => 'FEU', 'legal_name' => 'Fanfare Étudiante Universitaire de Strasbourg'],
            ['short_name' => 'AAEPS dite H2S', 'legal_name' => 'Association Amicale des Etudiants en Pharmacie de Strasbourg'],
            ['short_name' => 'Doctoneuro', 'legal_name' => 'Doctoneuro'],
            ['short_name' => 'Amicale LEA', 'legal_name' => 'Amicale LEA Strasbourg'],
            ['short_name' => 'ARTUS', 'legal_name' => 'Théâtre Universitaire de Strasbourg - ARTUS'],
            ['short_name' => 'RESHUS', 'legal_name' => 'Réseau des Etudiants Soignants des Hôpitaux Universitaires de Strasbourg'],
            ['short_name' => 'ASéAU', 'legal_name' => 'Association Strasbourgeoise des étudiants en Aménagement et Urbanisme'],
            ['short_name' => 'GAFE', 'legal_name' => ''],
            ['short_name' => 'AMESFA', 'legal_name' => ''],
            ['short_name' => 'ARIANE', 'legal_name' => ''],
            ['short_name' => 'AED', 'legal_name' => 'Association des Étudiants en droit et AES'],
            ['short_name' => 'ADEM', 'legal_name' => ''],
            ['short_name' => 'ADS', 'legal_name' => ''],
            ['short_name' => 'ASEO', 'legal_name' => 'Association Strasbourgeoise des Étudiants en Orthoptie'],
            ['short_name' => 'Plantago', 'legal_name' => ''],
            ['short_name' => 'AILES', 'legal_name' => 'Association de l\'IPAG pour Les Étudiants de Strasbourg'],
            ['short_name' => 'AMAdEUS', 'legal_name' => ''],
            ['short_name' => 'Métafor', 'legal_name' => ''],
            ['short_name' => 'Luciole', 'legal_name' => ''],
            ['short_name' => 'AAECS', 'legal_name' => 'Association Amicale des Étudiants en Chirurgie dentaire de Strasbourg'],
            ['short_name' => 'AAEMS', 'legal_name' => ''],
            ['short_name' => 'BEE COLOR', 'legal_name' => ''],
            ['short_name' => 'ADESI.E', 'legal_name' => ''],
            ['short_name' => 'BREI', 'legal_name' => ''],
            ['short_name' => 'BDE ICAM', 'legal_name' => 'Bureau des Etudiants de l’Icam Strasbourg-Europe'],
            ['short_name' => 'BDE INSA', 'legal_name' => 'Bureau des Elèves de l\'INSA de Strasbourg'],
            ['short_name' => 'UniGames', 'legal_name' => ''],
            ['short_name' => 'AEAS', 'legal_name' => 'Association des Etudiants d’Arts visuels de Strasbourg'],
            ['short_name' => 'ACA', 'legal_name' => 'Arts Culture Alsace'],
            ['short_name' => 'AE ENSCMu', 'legal_name' => 'Association des Élèves de l\'ENSCMu'],
            ['short_name' => 'AESES', 'legal_name' => ''],
            ['short_name' => 'AESS', 'legal_name' => 'Amicale STAPS Strasbourg'],
            ['short_name' => 'BDE Médiachill', 'legal_name' => ''],
        ];

        foreach ($organizations as $organization) {
            Organization::firstOrCreate(
                ['short_name' => $organization['short_name']],
                ['legal_name' => $organization['legal_name']],
                ['domain' => $organization['domain'] ?? null]
            );
        }
    }
}
