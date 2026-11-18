<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PublicationType;
use Illuminate\Database\Seeder;

/**
 * Données de référence initiales (cahier des charges § 5.1 et § 5.4).
 * Idempotent : n'écrase pas les modifications faites depuis l'administration.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['annonce-bon-plan', 'Annonce ou bon plan', false],
            ['evenement', 'Événement', true],
            ['recherche-partenaire', 'Recherche de partenaire', false],
            ['presentation-activite', "Présentation d'activité", false],
        ];

        foreach ($types as $order => [$slug, $name, $isEvent]) {
            PublicationType::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'is_event' => $isEvent,
                'sort_order' => $order,
            ]);
        }

        $categories = [
            ['collectivites', 'Collectivités'],
            ['stages-jeunesse', 'Stages et jeunesse'],
            ['coworking-sejours', 'Coworking et séjours'],
            ['commerce-terroir', 'Commerce et terroir'],
            ['formations', 'Formations'],
            ['partenariats-cooperations', 'Partenariats et coopérations'],
        ];

        foreach ($categories as $order => [$slug, $name]) {
            Category::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'sort_order' => $order,
            ]);
        }
    }
}
