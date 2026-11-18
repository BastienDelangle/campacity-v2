<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function userWithRole(Role $role, bool $verified = true): User
    {
        $factory = User::factory();
        $user = ($verified ? $factory : $factory->unverified())->create();

        return $user->assignRole($role->value);
    }

    public function test_admin_can_access_the_panel(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))->get('/admin')->assertOk();
    }

    public function test_moderator_can_access_the_panel(): void
    {
        $this->actingAs($this->userWithRole(Role::Moderator))->get('/admin')->assertOk();
    }

    public function test_regular_user_cannot_access_the_panel(): void
    {
        $this->actingAs($this->userWithRole(Role::User))->get('/admin')->assertForbidden();
    }

    public function test_unverified_admin_cannot_access_the_panel(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin, verified: false))->get('/admin')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_has_every_permission_and_moderator_only_moderation(): void
    {
        $admin = $this->userWithRole(Role::Admin);
        $moderator = $this->userWithRole(Role::Moderator);

        $this->assertTrue($admin->can(Permission::ManageSettings->value));
        $this->assertTrue($moderator->can(Permission::ModeratePublications->value));
        $this->assertFalse($moderator->can(Permission::ManageUsers->value));
    }

    public function test_seeder_does_not_override_permissions_changed_in_admin(): void
    {
        RoleModel::findByName(Role::Moderator->value)->revokePermissionTo(Permission::ManageReports->value);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse(RoleModel::findByName(Role::Moderator->value)->hasPermissionTo(Permission::ManageReports->value));
    }
}
