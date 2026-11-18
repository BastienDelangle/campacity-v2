<?php

namespace Tests\Feature\Account;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_contact_details_and_visibility(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '06 12 34 56 78',
            'website' => 'https://exemple.fr',
            'show_email' => '0',
            'show_phone' => '1',
            'show_website' => '1',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('06 12 34 56 78', $user->publicPhone());
        $this->assertNull($user->publicEmail());
    }

    public function test_profile_update_only_affects_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Autre']);

        $this->actingAs($user)->put(route('user-profile-information.update'), [
            'id' => $other->id,
            'name' => 'Modifié',
            'email' => $user->email,
        ]);

        $this->assertSame('Autre', $other->fresh()->name);
        $this->assertSame('Modifié', $user->fresh()->name);
    }

    public function test_account_deletion_requires_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('account.destroy'), ['password' => 'mauvais', 'confirmation' => '1'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');

        $this->assertFalse($user->fresh()->isAnonymized());
    }

    public function test_account_deletion_anonymizes_personal_data_and_logs_out(): void
    {
        $user = User::factory()->create(['email' => 'camille@example.org', 'phone' => '0600000000']);
        $user->assignRole(Role::User->value);
        $soloOrganization = Organization::factory()->ownedBy($user)->create();

        $this->actingAs($user)
            ->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => '1'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $user->refresh();
        $this->assertTrue($user->isAnonymized());
        $this->assertNotSame('camille@example.org', $user->email);
        $this->assertNull($user->phone);
        $this->assertFalse($user->hasRole(Role::User->value));
        $this->assertSoftDeleted($soloOrganization);

        $this->post('/login', ['email' => 'camille@example.org', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_sole_owner_of_a_shared_organization_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();
        Organization::factory()->ownedBy($user)->editedBy(User::factory()->create())->create(['name' => 'Asso partagée']);

        $this->actingAs($user)
            ->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => '1'])
            ->assertSessionHasErrors('account');

        $this->assertFalse($user->fresh()->isAnonymized());
        $this->assertAuthenticated();
    }
}
