<?php

namespace Tests\Feature\Organizations;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function abilities(): array
    {
        return [
            'propriétaire modifie' => ['owner', 'update', true],
            'éditeur modifie' => ['editor', 'update', true],
            'inconnu ne modifie pas' => ['stranger', 'update', false],
            'modérateur ne modifie pas' => ['moderator', 'update', false],
            'admin modifie' => ['admin', 'update', true],
            'propriétaire gère les responsables' => ['owner', 'manageMembers', true],
            'éditeur ne gère pas les responsables' => ['editor', 'manageMembers', false],
            'inconnu ne gère pas les responsables' => ['stranger', 'manageMembers', false],
            'admin gère les responsables' => ['admin', 'manageMembers', true],
            'propriétaire supprime' => ['owner', 'delete', true],
            'éditeur ne supprime pas' => ['editor', 'delete', false],
        ];
    }

    #[DataProvider('abilities')]
    public function test_organization_abilities(string $actor, string $ability, bool $expected): void
    {
        [$organization, $users] = $this->organizationWithActors();

        $this->assertSame($expected, $users[$actor]->can($ability, $organization));
    }

    public function test_inactive_organization_is_only_visible_to_members_and_managers(): void
    {
        [$organization, $users] = $this->organizationWithActors();
        $organization->forceFill(['is_active' => false])->save();

        $this->assertFalse(Gate::forUser(null)->allows('view', $organization));
        $this->assertFalse($users['stranger']->can('view', $organization));
        $this->assertTrue($users['editor']->can('view', $organization));
        $this->assertTrue($users['admin']->can('view', $organization));
    }

    /**
     * @return array{Organization, array<string, User>}
     */
    private function organizationWithActors(): array
    {
        $users = [
            'owner' => User::factory()->create(),
            'editor' => User::factory()->create(),
            'stranger' => User::factory()->create(),
            'moderator' => User::factory()->create()->assignRole(Role::Moderator->value),
            'admin' => User::factory()->create()->assignRole(Role::Admin->value),
        ];

        $organization = Organization::factory()
            ->ownedBy($users['owner'])
            ->editedBy($users['editor'])
            ->create();

        return [$organization, $users];
    }
}
