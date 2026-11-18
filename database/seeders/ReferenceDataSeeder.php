<?php

namespace Database\Seeders;

use App\Enums\VisibilityKind;
use App\Models\Category;
use App\Models\PublicationType;
use App\Models\VisibilityProduct;
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

        // Offres de visibilité de la maquette V1 : prix INDICATIFS, à valider
        // et modifiables dans l'administration. L'offre « Découvrir » (gratuite)
        // correspond à la publication classique et n'est pas un produit.
        $products = [
            ['mettre-en-avant', 'Mettre en avant', VisibilityKind::Featured, 2000, 30, 10, 'Votre publication est mise en avant pendant un mois dans les résultats et sur la carte.'],
            ['booster', 'Booster', VisibilityKind::Boost, 5000, 30, 20, 'Visibilité renforcée : votre publication passe devant les publications mises en avant.'],
            ['partenaire-premium', 'Partenaire premium', VisibilityKind::PremiumPartner, 10000, 30, 0, 'Une page vitrine dédiée à votre organisation, présente dans l\'annuaire des partenaires et sur l\'accueil.'],
        ];

        foreach ($products as $order => [$slug, $name, $kind, $price, $days, $weight, $description]) {
            VisibilityProduct::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'kind' => $kind,
                'price_cents' => $price,
                'duration_days' => $days,
                'ranking_weight' => $weight,
                'description' => $description,
                'sort_order' => $order,
            ]);
        }
    }
}
