<?php

namespace Tests\Feature\Contact;

use App\Models\ContactMessage;
use App\Models\Publication;
use App\Models\User;
use App\Notifications\NewContactMessage;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\SubmitsPublicForms;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase, SubmitsPublicForms;

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            ...$this->humanFormFields(),
            'sender_name' => 'Léa Dupont',
            'sender_email' => 'lea@example.org',
            'message' => 'Bonjour, votre gîte serait-il disponible pour une équipe de six personnes en mars ?',
            'consent' => '1',
        ], $overrides);
    }

    public function test_a_visitor_can_contact_the_author_who_is_notified(): void
    {
        Notification::fake();
        $author = User::factory()->create(['email' => 'auteur-prive@example.org']);
        $publication = Publication::factory()->for($author, 'author')->published()->create();

        $this->post(route('publications.contact', $publication), $this->payload())
            ->assertRedirect(route('publications.show', $publication->slug).'#contacter')
            ->assertSessionHas('contact_status');

        $message = ContactMessage::sole();
        $this->assertTrue($message->recipient->is($author));
        $this->assertSame('lea@example.org', $message->sender_email);

        Notification::assertSentTo($author, NewContactMessage::class, function (NewContactMessage $notification) use ($author) {
            $mail = $notification->toMail($author);

            return $mail->replyTo === [['lea@example.org', 'Léa Dupont']];
        });
    }

    public function test_the_publication_page_never_shows_the_private_email(): void
    {
        $author = User::factory()->create(['email' => 'auteur-prive@example.org', 'show_email' => false]);
        $publication = Publication::factory()->for($author, 'author')->published()->create();

        $this->get(route('publications.show', $publication->slug))
            ->assertOk()
            ->assertSee('Envoyer un message')
            ->assertDontSee('auteur-prive@example.org');
    }

    public function test_contact_form_is_validated(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->post(route('publications.contact', $publication), $this->payload(['sender_email' => 'pas-un-email', 'message' => 'Court', 'consent' => '']))
            ->assertSessionHasErrorsIn('contact', ['sender_email', 'message', 'consent']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_spam_is_rejected(): void
    {
        $publication = Publication::factory()->published()->create();

        $this->post(route('publications.contact', $publication), $this->payload(['website_confirmation' => 'x']))
            ->assertSessionHasErrorsIn('contact', 'form');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_is_unavailable_when_disabled_or_not_public(): void
    {
        $disabled = Publication::factory()->published()->create(['contact_form_enabled' => false]);
        $pending = Publication::factory()->pending()->create();

        $this->post(route('publications.contact', $disabled), $this->payload())->assertNotFound();
        $this->post(route('publications.contact', $pending), $this->payload())->assertNotFound();
        $this->get(route('publications.show', $disabled->slug))->assertDontSee('Envoyer un message');
    }

    public function test_public_forms_are_rate_limited(): void
    {
        $publication = Publication::factory()->published()->create();

        foreach (range(1, 3) as $i) {
            $this->post(route('publications.contact', $publication), $this->payload());
        }

        $this->post(route('publications.contact', $publication), $this->payload())->assertTooManyRequests();
    }

    public function test_recipient_sees_their_messages_and_others_cannot(): void
    {
        $recipient = User::factory()->create();
        $message = ContactMessage::factory()->for($recipient, 'recipient')->create(['sender_name' => 'Paul Martin']);
        ContactMessage::factory()->create(['sender_name' => 'Message d\'un autre']);

        $this->actingAs($recipient)->get(route('account.messages.index'))
            ->assertOk()->assertSee('Paul Martin')->assertDontSee('Message d\'un autre');

        $this->actingAs($recipient)->get(route('account.messages.show', $message))->assertOk();
        $this->assertNotNull($message->fresh()->read_at);

        $this->actingAs(User::factory()->create())->get(route('account.messages.show', $message))->assertNotFound();
        $this->actingAs(User::factory()->create())->delete(route('account.messages.destroy', $message))->assertNotFound();
    }

    public function test_old_messages_are_pruned(): void
    {
        config(['campacity.contact_message_retention_days' => 30]);
        $old = ContactMessage::factory()->create(['created_at' => now()->subDays(31)]);
        $recent = ContactMessage::factory()->create();

        $this->artisan('model:prune', ['--model' => [ContactMessage::class]]);

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_welcome_email_is_sent_after_email_verification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url);

        Notification::assertSentTo($user, WelcomeNotification::class);
    }
}
