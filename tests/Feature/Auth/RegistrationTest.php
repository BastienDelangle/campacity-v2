<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_registration_page_is_displayed(): void
    {
        $this->get('/register')->assertOk()->assertSee('Créer un compte');
    }

    public function test_a_user_can_register_and_receives_the_user_role(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Camille Martin',
            'email' => 'camille@example.org',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
            'terms' => '1',
        ])->assertRedirect('/espace');

        $user = User::where('email', 'camille@example.org')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(Role::User->value));
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_requires_consent(): void
    {
        $this->post('/register', [
            'name' => 'Camille Martin',
            'email' => 'camille@example.org',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
    }

    public function test_unverified_users_cannot_reach_their_space(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/espace')->assertRedirect('/email/verify');
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/espace?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
