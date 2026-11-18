<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_log_in_and_out(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/espace');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'mauvais'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_verified_user_can_see_and_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/espace/profil')->assertOk()->assertSee($user->email);

        $this->actingAs($user)->put('/user/profile-information', [
            'name' => 'Nouveau Nom',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nouveau Nom', $user->fresh()->name);
    }
}
