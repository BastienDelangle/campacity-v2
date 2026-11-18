<?php

namespace Tests\Feature\Admin;

use App\Enums\OrganizationRole;
use App\Enums\Role;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\RelationManagers\MembersRelationManager;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminResourcesAccessTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/admin/users'])]
    #[TestWith(['/admin/organizations'])]
    #[TestWith(['/admin/territories'])]
    #[TestWith(['/admin/communes'])]
    #[TestWith(['/admin/visibility-products'])]
    #[TestWith(['/admin/visibility-purchases'])]
    #[TestWith(['/admin/payments'])]
    #[TestWith(['/admin/partner-profiles'])]
    #[TestWith(['/admin/partner-profiles/create'])]
    #[TestWith(['/admin/publication-reports'])]
    public function test_admin_can_list_resources(string $url): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);

        $this->actingAs($admin)->get($url)->assertOk();
    }

    #[TestWith(['/admin/users'])]
    #[TestWith(['/admin/organizations'])]
    #[TestWith(['/admin/territories'])]
    #[TestWith(['/admin/payments'])]
    #[TestWith(['/admin/partner-profiles'])]
    #[TestWith(['/admin/visibility-products'])]
    public function test_moderator_cannot_access_management_resources(string $url): void
    {
        $moderator = User::factory()->create()->assignRole(Role::Moderator->value);

        $this->actingAs($moderator)->get($url)->assertForbidden();
    }

    public function test_admin_forms_render(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        $user = User::factory()->create();
        $organization = Organization::factory()->ownedBy($user)->create();

        $this->actingAs($admin)->get('/admin/users/create')->assertOk();
        $this->actingAs($admin)->get("/admin/users/{$user->id}/edit")->assertOk()->assertSee('Rôles');
        $this->actingAs($admin)->get("/admin/organizations/{$organization->slug}/edit")->assertOk();
    }

    public function test_admin_members_manager_lists_members_and_keeps_the_last_owner(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->actingAs($admin);

        Livewire::test(MembersRelationManager::class, ['ownerRecord' => $organization, 'pageClass' => EditOrganization::class])
            ->assertCanSeeTableRecords([$owner])
            ->callTableAction('remove', $owner)
            ->assertNotified("L'organisation doit conserver au moins un propriétaire.");

        $this->assertSame(OrganizationRole::Owner, $organization->roleOf($owner));
    }

    public function test_admin_cannot_change_their_own_roles(): void
    {
        $admin = User::factory()->create()->assignRole(Role::Admin->value);
        $other = User::factory()->create();

        $this->assertFalse($admin->can('assignRoles', $admin));
        $this->assertTrue($admin->can('assignRoles', $other));
    }
}
