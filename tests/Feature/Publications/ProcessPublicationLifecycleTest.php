<?php

namespace Tests\Feature\Publications;

use App\Enums\PublicationStatus;
use App\Models\Publication;
use App\Models\PublicationType;
use App\Notifications\PublicationExpiringSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcessPublicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_publications_are_marked_expired(): void
    {
        $due = Publication::factory()->published()->create(['expires_at' => now()->subHour()]);
        $current = Publication::factory()->published()->create(['expires_at' => now()->addMonth()]);

        $this->artisan('publications:process-lifecycle')->assertSuccessful();

        $this->assertSame(PublicationStatus::Expired, $due->fresh()->status);
        $this->assertSame(PublicationStatus::Published, $current->fresh()->status);
    }

    public function test_finished_events_are_archived(): void
    {
        $type = PublicationType::factory()->event()->create();
        $finished = Publication::factory()->published()->for($type, 'type')->create();
        $finished->event()->create(['starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(2)]);
        $upcoming = Publication::factory()->published()->for($type, 'type')->create();
        $upcoming->event()->create(['starts_at' => now()->addDays(3)]);

        $this->artisan('publications:process-lifecycle');

        $this->assertSame(PublicationStatus::Archived, $finished->fresh()->status);
        $this->assertSame(PublicationStatus::Published, $upcoming->fresh()->status);
    }

    public function test_expiration_reminder_is_sent_once(): void
    {
        Notification::fake();
        $publication = Publication::factory()->published()->create(['expires_at' => now()->addDays(3)]);
        Publication::factory()->published()->create(['expires_at' => now()->addDays(40)]);

        $this->artisan('publications:process-lifecycle');
        $this->artisan('publications:process-lifecycle');

        Notification::assertSentToTimes($publication->author, PublicationExpiringSoon::class, 1);
        Notification::assertCount(1);
    }
}
