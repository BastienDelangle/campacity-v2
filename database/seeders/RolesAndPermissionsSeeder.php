<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent : crée les rôles et permissions manquants sans écraser
 * les ajustements faits depuis l'administration.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions(array_map(
                    fn (PermissionEnum $p) => $p->value,
                    $roleEnum->defaultPermissions(),
                ));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
