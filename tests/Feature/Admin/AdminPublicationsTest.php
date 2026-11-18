<?php

namespace Tests\Feature\Admin;

use App\Enums\PublicationStatus;
use App\Enums\Role;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Publications\Pages\ListPublications;
use App\Models\Category;
use App\Models\Publication;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminPublicationsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function userWithRole(Role $role): User
    {
        return User::factory()->create()->assignRole($role->value);
    }

    #[TestWith(['/admin/publications'])]
    #[TestWith(['/admin/publication-types'])]
    #[TestWith(['/admin/categories'])]
    #[TestWith(['/admin/parametres'])]
    public function test_admin_can_open_phase_3_screens(string $url): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))->get($url)->assertOk();
    }

    public function test_moderator_sees_publications_but_not_taxonomy_or_settings(): void
    {
        $moderator = $this->userWithRole(Role::Moderator);

        $this->actingAs($moderator)->get('/admin/publications')->assertOk();
        $this->actingAs($moderator)->get('/admin/categories')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/parametres')->assertForbidden();
    }

    public function test_moderator_can_approve_a_pending_publication(): void
    {
        $publication = Publication::factory()->pending()->create();
        $this->actingAs($this->userWithRole(Role::Moderator));

        Livewire::test(ListPublications::class)
            ->callTableAction('approve', $publication)
            ->assertNotified('Publication validée et mise en ligne');

        $publication->refresh();
        $this->assertSame(PublicationStatus::Published, $publication->status);
        $this->assertTrue($publication->isPubliclyVisible());
    }

    public function test_settings_are_saved_and_applied(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin));

        Livewire::test(ManageSettings::class)
            ->fillForm(['publication_duration_days' => 30, 'publication_edits_require_moderation' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $this->assertSame(30, $settings->int('publication_duration_days'));
        $this->assertFalse($settings->bool('publication_edits_require_moderation'));
    }

    public function test_settings_reject_invalid_values(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin));

        Livewire::test(ManageSettings::class)
            ->fillForm(['publication_duration_days' => 0])
            ->call('save')
            ->assertHasFormErrors(['publication_duration_days' => 'min']);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole(Role::Admin);
        $used = Publication::factory()->create()->category;
        $unused = Category::factory()->create();

        $this->assertFalse($admin->can('delete', $used));
        $this->assertTrue($admin->can('delete', $unused));

        $this->actingAs($admin);
        Livewire::test(ManageCategories::class)->assertTableActionHidden('delete', $used);
    }
}
