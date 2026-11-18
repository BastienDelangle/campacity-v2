<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comptes de développement local. Ne jamais exécuter en production.
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
    }
}
