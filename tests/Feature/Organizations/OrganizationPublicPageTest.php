<?php

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_shows_identity_and_hides_private_contacts(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Ferme des Lilas',
            'email' => 'prive@ferme.test',
            'phone' => '01 23 45 67 89',
            'show_email' => false,
            'show_phone' => false,
        ]);

        $this->get(route('organizations.show', $organization))
            ->assertOk()
            ->assertSee('Ferme des Lilas')
            ->assertSee($organization->commune->name)
            ->assertDontSee('prive@ferme.test')
            ->assertDontSee('01 23 45 67 89');
    }

    public function test_public_contacts_are_shown_when_allowed(): void
    {
        $organization = Organization::factory()->create([
            'email' => 'public@ferme.test',
            'show_email' => true,
        ]);

        $this->get(route('organizations.show', $organization))->assertSee('public@ferme.test');
    }

    public function test_inactive_organization_is_not_public(): void
    {
        $organization = Organization::factory()->inactive()->create();

        $this->get(route('organizations.show', $organization))->assertForbidden();
    }

    public function test_deleted_organization_returns_404(): void
    {
        $organization = Organization::factory()->create();
        $organization->delete();

        $this->get(route('organizations.show', $organization))->assertNotFound();
    }
}
