<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_sent_to_the_dashboard_after_login(): void
    {
        $admin = $this->account(['role' => 'admin']);

        $this->withSession(['url.intended' => route('customer.bookings')])
            ->post(route('login'), [
                'email' => $admin->email,
                'password' => 'Account-Password9!',
            ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_vendor_can_log_in_with_remember_me_and_log_out(): void
    {
        $vendor = $this->account(['role' => 'vendor']);
        $this->get(route('login'))->assertOk()->assertSee('name="_token"', false);

        $this->post(route('login'), [
            'email' => strtoupper($vendor->email),
            'password' => 'Account-Password9!',
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
            'password' => 'Account-Password9!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_and_login_reject_passwords_missing_strength_requirements(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('password-requirements', false);
        $user = $this->account(['password' => 'legacy-password']);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'legacy-password'])
            ->assertSessionHasErrors('password');
        $this->assertGuest();
        $registration = [
            'first_name' => 'Demo', 'last_name' => 'Registration', 'email' => 'new@example.test',
            'phone' => '0700000000', 'location' => 'عمّان', 'gender' => 'female', 'account_type' => 'customer',
        ];
        foreach (['uppercase9!', 'UPPERCASE9!', 'Password!', 'Password9', 'Pass9!', 'Strong9!'.str_repeat('é', 33)] as $weak) {
            $this->post(route('register'), $registration + ['password' => $weak, 'password_confirmation' => $weak])
                ->assertSessionHasErrors('password');
        }
        $this->post(route('register'), $registration + ['password' => 'Strong9!', 'password_confirmation' => 'Strong8!'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => $registration['email']]);
    }

    public function test_registration_saves_an_optional_profile_image(): void
    {
        $disk = Storage::fake('local');
        $data = [
            'first_name' => 'Demo', 'last_name' => 'Photo', 'email' => 'photo@example.test',
            'phone' => '0700000000', 'location' => 'عمّان', 'gender' => 'female', 'account_type' => 'customer',
            'password' => 'Strong9!', 'password_confirmation' => 'Strong9!',
        ];
        $this->get(route('register'))->assertOk()->assertSee('name="profile_image"', false);
        $this->post(route('register'), $data + ['profile_image' => UploadedFile::fake()->create('document.pdf')])
            ->assertSessionHasErrors('profile_image');
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Xk4QAAAAASUVORK5CYII=');
        $this->post(route('register'), $data + [
            'profile_image' => UploadedFile::fake()->createWithContent('profile.png', $png),
        ])->assertRedirect(route('home'));
        $user = User::whereEmail($data['email'])->firstOrFail();
        $disk->assertExists($user->profile_image);
        $imageUrl = route('account.picture.show', $user);
        $this->get(route('account.profile'))->assertOk()->assertSee($imageUrl);
        $this->get($imageUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
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
            'password' => 'Replacement9!',
            'password_confirmation' => 'Replacement9!',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('Replacement9!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->get(route('password.confirm'))->assertOk();
        $this->post(route('password.confirm'), ['password' => 'Replacement9!'])
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
            'password' => 'Account-Password9!',
        ])->refresh();
    }
}
