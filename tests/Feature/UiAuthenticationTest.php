<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_log_in_with_remember_me_and_log_out(): void
    {
        $vendor = $this->account(['role' => 'vendor']);
        $this->get(route('login'))->assertOk()->assertSee('name="_token"', false);

        $this->post(route('login'), [
            'email' => strtoupper($vendor->email),
            'password' => 'account-password',
            'remember' => '1',
        ])->assertRedirect(route('vendor.profile'));

        $this->assertAuthenticatedAs($vendor);
        $this->assertNotEmpty($vendor->fresh()->getRememberToken());
        $this->get(route('register'))->assertRedirect(route('home'));
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get(route('vendor.wallet'))->assertRedirect(route('login'));
    }

    public function test_suspended_account_cannot_log_in(): void
    {
        $user = $this->account(['status' => 'suspended']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'account-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_reset_sends_notification_and_consumes_token(): void
    {
        Notification::fake();
        $user = $this->account();

        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Notification::sent($user, ResetPassword::class)->sole()->token;

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->get(route('password.confirm'))->assertOk();
        $this->post(route('password.confirm'), ['password' => 'replacement-password'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('auth.password_confirmed_at');
    }

    private function account(array $attributes = []): User
    {
        return User::forceCreate($attributes + [
            'first_name' => 'Demo',
            'last_name' => 'Account',
            'email' => 'account@example.com',
            'phone' => '0000000000',
            'location' => 'Amman',
            'gender' => 'female',
            'password' => 'account-password',
        ])->refresh();
    }
}
