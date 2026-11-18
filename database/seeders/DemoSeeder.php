<?php

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Commune;
use App\Models\Organization;
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
    }
}
