<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Enums\PublicationNature;
use App\Enums\PublicationOrigin;
use App\Enums\PublicationStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Organization;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de développement local, volontairement fictives et identifiées
 * comme telles. Ne jamais exécuter en production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['admin@campacity.test', 'Admin Campacity', Role::Admin],
            ['moderateur@campacity.test', 'Modérateur Campacity', Role::Moderator],
            ['utilisateur@campacity.test', 'Utilisateur Démo', Role::User],
        ];

        foreach ($accounts as [$email, $name, $role]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$role->value]);
        }

        $territory = Territory::firstOrCreate(['slug' => 'territoire-demo'], [
            'name' => 'Territoire de démonstration',
            'center_latitude' => 49.1167,
            'center_longitude' => -1.0833,
            'default_zoom' => 10,
        ]);

        foreach ([['Ville démo', '50000', 49.1167, -1.0833], ['Village démo', '50100', 49.2, -1.2]] as [$name, $postalCode, $lat, $lng]) {
            Commune::firstOrCreate(['name' => $name, 'territory_id' => $territory->id], [
                'postal_code' => $postalCode,
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
        }

        if (! Organization::where('slug', 'association-demo')->exists()) {
            $organization = new Organization([
                'name' => 'Association démo',
                'type' => OrganizationType::Association,
                'description' => 'Organisation fictive pour le développement local.',
                'commune_id' => Commune::where('territory_id', $territory->id)->value('id'),
            ]);
            $organization->slug = 'association-demo';
            $organization->save();
            $organization->members()->attach(User::where('email', 'utilisateur@campacity.test')->value('id'), ['role' => 'owner']);
        }

        $this->seedPublications();
    }

    /**
     * Quelques publications fictives, clairement marquées « démo ».
     */
    private function seedPublications(): void
    {
        $author = User::where('email', 'utilisateur@campacity.test')->first();
        $organization = Organization::where('slug', 'association-demo')->first();
        $communes = Commune::whereHas('territory', fn ($q) => $q->where('slug', 'territoire-demo'))->pluck('id');

        $items = [
            ['[Démo] Gîte disponible pour séjours de télétravail', 'annonce-bon-plan', 'coworking-sejours', PublicationNature::Offer, PublicationOrigin::Rural, PublicationStatus::Published],
            ['[Démo] Recherche stages en ville pour jeunes ruraux', 'recherche-partenaire', 'stages-jeunesse', PublicationNature::Request, PublicationOrigin::Rural, PublicationStatus::Published],
            ['[Démo] Marché de producteurs locaux', 'evenement', 'commerce-terroir', PublicationNature::Offer, PublicationOrigin::Both, PublicationStatus::Published],
            ['[Démo] Collectivité cherche partenaires pour un centre de vacances', 'recherche-partenaire', 'collectivites', PublicationNature::Request, PublicationOrigin::Urban, PublicationStatus::Pending],
        ];

        foreach ($items as $index => [$title, $type, $category, $nature, $origin, $status]) {
            if (Publication::where('title', $title)->exists()) {
                continue;
            }

            $publication = new Publication([
                'title' => $title,
                'description' => "Publication fictive de démonstration, à ne pas considérer comme une donnée réelle.\n\nElle sert à vérifier l'affichage des listes, des fiches et de la carte.",
                'publication_type_id' => PublicationType::where('slug', $type)->value('id'),
                'category_id' => Category::where('slug', $category)->value('id'),
                'nature' => $nature,
                'origin' => $origin,
                'commune_id' => $communes[$index % max(1, $communes->count())] ?? null,
                'organization_id' => $index === 0 ? $organization?->id : null,
            ]);
            $publication->user_id = $author->id;
            $publication->status = $status;
            $publication->submitted_at = now();
            if ($status === PublicationStatus::Published) {
                $publication->published_at = now();
                $publication->expires_at = now()->addDays(90);
            }
            $publication->save();

            if ($type === 'evenement') {
                $publication->event()->create(['starts_at' => now()->addWeeks(2)->setTime(9, 0), 'ends_at' => now()->addWeeks(2)->setTime(13, 0), 'venue' => 'Place du village (démo)']);
            }
        }
    }
}
