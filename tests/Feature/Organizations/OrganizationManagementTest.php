<?php

namespace Tests\Feature\Organizations;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Models\Commune;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('account.organizations.create'))->assertRedirect(route('login'));
    }

    public function test_create_and_edit_forms_render(): void
    {
        $owner = User::factory()->create();
        $commune = Commune::factory()->create(['name' => 'Saint-Lô', 'postal_code' => '50000']);
        $organization = Organization::factory()->ownedBy($owner)->create(['commune_id' => $commune->id]);

        $this->actingAs($owner)->get(route('account.organizations.create'))->assertOk()->assertSee('commune_id-search', false);
        $this->actingAs($owner)->get(route('account.organizations.edit', $organization))
            ->assertOk()
            ->assertSee('Ajouter un responsable')
            ->assertSee('Saint-Lô (50000)');
    }

    public function test_user_can_create_an_organization_and_becomes_its_owner(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $commune = Commune::factory()->create();

        $response = $this->actingAs($user)->post(route('account.organizations.store'), [
            'name' => 'Les Jardins Partagés',
            'type' => OrganizationType::Association->value,
            'description' => 'Une association de jardiniers.',
            'commune_id' => $commune->id,
            'email' => 'contact@jardins.test',
            'show_email' => '1',
            'links' => [['label' => 'Facebook', 'url' => 'https://facebook.com/jardins'], ['label' => '', 'url' => '']],
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]);

        $organization = Organization::where('name', 'Les Jardins Partagés')->firstOrFail();
        $response->assertRedirect(route('account.organizations.edit', $organization));

        $this->assertSame('les-jardins-partages', $organization->slug);
        $this->assertSame(OrganizationRole::Owner, $organization->roleOf($user));
        $this->assertEquals([['label' => 'Facebook', 'url' => 'https://facebook.com/jardins']], $organization->links);
        Storage::disk('public')->assertExists($organization->logo_path);
    }

    public function test_creation_requires_name_and_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('account.organizations.store'), [])
            ->assertSessionHasErrors([
                'name' => 'Le champ nom est obligatoire.',
                'type' => "Le champ type d'organisation est obligatoire.",
            ]);
    }

    public function test_svg_logo_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post(route('account.organizations.store'), [
                'name' => 'Test',
                'type' => OrganizationType::Company->value,
                'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_user_cannot_edit_an_organization_they_do_not_manage(): void
    {
        $organization = Organization::factory()->ownedBy(User::factory()->create())->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->get(route('account.organizations.edit', $organization))->assertForbidden();
        $this->actingAs($stranger)
            ->put(route('account.organizations.update', $organization), ['name' => 'Piraté', 'type' => 'other'])
            ->assertForbidden();

        $this->assertNotSame('Piraté', $organization->fresh()->name);
    }

    public function test_editor_can_update_the_organization_but_slug_is_kept(): void
    {
        $editor = User::factory()->create();
        $organization = Organization::factory()->editedBy($editor)->create(['name' => 'Ancien nom']);
        $slug = $organization->slug;

        $this->actingAs($editor)
            ->put(route('account.organizations.update', $organization), ['name' => 'Nouveau nom', 'type' => OrganizationType::Producer->value])
            ->assertRedirect(route('account.organizations.edit', $organization));

        $organization->refresh();
        $this->assertSame('Nouveau nom', $organization->name);
        $this->assertSame($slug, $organization->slug);
    }

    public function test_user_only_sees_their_own_organizations(): void
    {
        $user = User::factory()->create();
        Organization::factory()->ownedBy($user)->create(['name' => 'Mon asso']);
        Organization::factory()->ownedBy(User::factory()->create())->create(['name' => 'Asso voisine']);

        $this->actingAs($user)->get(route('account.organizations.index'))
            ->assertOk()
            ->assertSee('Mon asso')
            ->assertDontSee('Asso voisine');
    }

    public function test_owner_can_add_an_existing_user_as_editor(): void
    {
        $owner = User::factory()->create();
        $colleague = User::factory()->create(['email' => 'collegue@example.org']);
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('account.organizations.members.store', $organization), ['member_email' => 'collegue@example.org', 'member_role' => 'editor'])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrganizationRole::Editor, $organization->roleOf($colleague));
    }

    public function test_adding_an_unknown_email_returns_an_error(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('account.organizations.members.store', $organization), ['member_email' => 'inconnu@example.org', 'member_role' => 'editor'])
            ->assertSessionHasErrorsIn('members', 'member_email');
    }

    public function test_editor_cannot_add_members(): void
    {
        $editor = User::factory()->create();
        $organization = Organization::factory()->editedBy($editor)->create();
        User::factory()->create(['email' => 'autre@example.org']);

        $this->actingAs($editor)
            ->post(route('account.organizations.members.store', $organization), ['member_email' => 'autre@example.org', 'member_role' => 'owner'])
            ->assertForbidden();
    }

    public function test_last_owner_cannot_leave_or_be_demoted(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->delete(route('account.organizations.members.destroy', [$organization, $owner]))
            ->assertSessionHasErrors(['member' => "L'organisation doit conserver au moins un propriétaire."]);

        $this->actingAs($owner)
            ->put(route('account.organizations.members.update', [$organization, $owner]), ['role' => 'editor'])
            ->assertSessionHasErrors('member');

        $this->assertSame(OrganizationRole::Owner, $organization->roleOf($owner));
    }

    public function test_editor_can_leave_the_organization(): void
    {
        $editor = User::factory()->create();
        $organization = Organization::factory()->ownedBy(User::factory()->create())->editedBy($editor)->create();

        $this->actingAs($editor)
            ->delete(route('account.organizations.members.destroy', [$organization, $editor]))
            ->assertRedirect(route('account.organizations.index'));

        $this->assertNull($organization->roleOf($editor));
    }

    public function test_member_routes_return_404_for_a_user_outside_the_organization(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();
        $outsider = User::factory()->create();

        $this->actingAs($owner)
            ->delete(route('account.organizations.members.destroy', [$organization, $outsider]))
            ->assertNotFound();
    }
}
