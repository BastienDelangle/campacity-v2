<?php

namespace Tests\Feature\Visibility;

use App\Enums\PurchaseStatus;
use App\Models\Organization;
use App\Models\PartnerProfile;
use App\Models\Publication;
use App\Models\User;
use App\Models\VisibilityProduct;
use App\Models\VisibilityPurchase;
use App\Payments\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_premium_partner_has_a_dedicated_page_listed_in_the_directory_and_on_home(): void
    {
        $profile = PartnerProfile::factory()->visible()->create(['headline' => 'Producteurs engagés depuis 1990']);
        $organization = $profile->organization;
        Publication::factory()->published()->create(['organization_id' => $organization->id, 'title' => 'Paniers de saison']);

        $this->get(route('partners.show', $organization))
            ->assertOk()
            ->assertSee($organization->name)
            ->assertSee('Producteurs engagés depuis 1990')
            ->assertSee('Paniers de saison')
            ->assertSee('Partenaire premium');

        $this->get(route('partners.index'))->assertSee($organization->name);
        $this->get(route('home'))->assertSee('Ils font avancer les coopérations')->assertSee($organization->name);
        $this->get(route('organizations.show', $organization))->assertSee('voir la vitrine');
    }

    public function test_partner_page_is_hidden_outside_its_visibility_period(): void
    {
        $expired = PartnerProfile::factory()->create(['visible_from' => now()->subMonths(2), 'visible_until' => now()->subDay()]);
        $never = PartnerProfile::factory()->create();

        $this->get(route('partners.show', $expired->organization))->assertNotFound();
        $this->get(route('partners.show', $never->organization))->assertNotFound();
        $this->get(route('partners.index'))->assertDontSee($expired->organization->name);
    }

    public function test_owner_subscribes_and_the_showcase_goes_live_after_payment(): void
    {
        $this->freezeSecond();
        $owner = User::factory()->create();
        $organization = Organization::factory()->ownedBy($owner)->create();
        $product = VisibilityProduct::where('slug', 'partenaire-premium')->firstOrFail();

        $this->actingAs($owner)->post(route('account.organizations.partner.subscribe', $organization), ['product_id' => $product->id, 'terms' => '1']);

        $purchase = VisibilityPurchase::sole();
        $this->assertSame(PurchaseStatus::PendingPayment, $purchase->status);
        $this->assertFalse($organization->partnerProfile->isVisible());

        // Avant paiement : le propriétaire voit un aperçu, le public rien.
        $this->actingAs($owner)->get(route('partners.show', $organization))->assertOk()->assertSee('Aperçu');
        $this->post('/logout');
        $this->get(route('partners.show', $organization))->assertNotFound();

        app(PaymentManager::class)->markSucceeded($purchase->latestPayment(), 'VIR-1');

        $profile = $organization->partnerProfile()->first();
        $this->assertTrue($profile->isVisible());
        $this->assertTrue($profile->visible_until->equalTo(now()->addDays(30)));
        $this->get(route('partners.show', $organization))->assertOk();
    }

    public function test_only_owners_can_subscribe_but_editors_can_edit_the_showcase(): void
    {
        $editor = User::factory()->create();
        $organization = Organization::factory()->ownedBy(User::factory()->create())->editedBy($editor)->create();
        $profile = PartnerProfile::factory()->for($organization)->create();
        $product = VisibilityProduct::where('slug', 'partenaire-premium')->firstOrFail();

        $this->actingAs($editor)
            ->post(route('account.organizations.partner.subscribe', $organization), ['product_id' => $product->id, 'terms' => '1'])
            ->assertForbidden();

        $this->actingAs($editor)
            ->put(route('account.organizations.partner.update', $organization), ['headline' => 'Nouvelle accroche'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Nouvelle accroche', $profile->fresh()->headline);
    }

    public function test_a_stranger_cannot_manage_a_showcase(): void
    {
        $organization = Organization::factory()->ownedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())->get(route('account.organizations.partner.edit', $organization))->assertForbidden();
    }

    public function test_offers_page_lists_administrable_prices(): void
    {
        VisibilityProduct::where('slug', 'booster')->update(['price_cents' => 4500]);

        $this->get(route('visibility.offers'))
            ->assertOk()
            ->assertSee('Gratuit')
            ->assertSee('45,00')
            ->assertSee('100,00');
    }
}
