<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_page_and_return_after_registering(): void
    {
        $this->get(route('vendor.apply'))->assertOk();
        $this->post(route('vendor.apply'))->assertRedirect(route('login'));
        $this->get(route('vendor.apply', ['start' => 1]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('vendor.apply'));

        $this->post(route('register'), [
            'first_name' => 'Demo',
            'last_name' => 'Applicant',
            'email' => 'applicant@example.com',
            'phone' => '0000000000',
            'location' => 'Amman',
            'gender' => 'female',
            'password' => 'Test-Password9!',
            'password_confirmation' => 'Test-Password9!',
        ])->assertRedirect(route('vendor.apply'));

        $this->get(route('vendor.apply'))->assertOk()->assertSee('multipart/form-data');
    }

    public function test_customer_can_submit_once_and_permit_is_stored_privately(): void
    {
        $privateDisk = Storage::fake('local');
        $publicDisk = Storage::fake('public');
        $user = $this->createAccount();
        $this->actingAs($user);

        $this->post(route('vendor.apply'), [
            'experience_years' => 5,
            'work_type' => 'Plumbing',
            'bio' => 'Residential plumbing and maintenance.',
            'permit_expiry_date' => now()->addYear()->toDateString(),
            'work_permit' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
            'user_id' => 999,
            'application_status' => 'approved',
        ])->assertRedirect(route('vendor.apply'));

        $application = DB::table('vendor_profiles')->where('user_id', $user->id)->first();
        $this->assertNotNull($application);
        $this->assertSame('pending', $application->application_status);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'customer']);
        $privateDisk->assertExists($application->work_permit_path);
        $publicDisk->assertMissing($application->work_permit_path);
        $this->get(route('vendor.apply'))->assertOk()
            ->assertSee('قيد المراجعة')
            ->assertDontSee($application->work_permit_path);

        $this->post(route('vendor.apply'))->assertRedirect(route('vendor.apply'));
        $this->assertDatabaseCount('vendor_profiles', 1);
        $this->assertCount(1, $privateDisk->allFiles());
    }

    public function test_invalid_permit_and_ineligible_accounts_are_rejected(): void
    {
        $privateDisk = Storage::fake('local');
        $user = $this->createAccount();

        $this->actingAs($user)->from(route('vendor.apply'))->post(route('vendor.apply'), [
            'experience_years' => 5,
            'work_type' => 'Plumbing',
            'bio' => 'Residential plumbing and maintenance.',
            'permit_expiry_date' => now()->subDay()->toDateString(),
            'work_permit' => UploadedFile::fake()->create('permit.txt', 100, 'text/plain'),
        ])->assertSessionHasErrors(['permit_expiry_date', 'work_permit']);

        $user->forceFill(['status' => 'suspended'])->save();
        $this->actingAs($user)->post(route('vendor.apply'))->assertForbidden();
        $user->forceFill(['status' => 'active', 'role' => 'vendor'])->save();
        $this->actingAs($user)->post(route('vendor.apply'))->assertForbidden();
        $this->assertDatabaseCount('vendor_profiles', 0);
        $privateDisk->assertDirectoryEmpty('vendor-permits');
    }

    private function createAccount(): User
    {
        return User::forceCreate([
            'first_name' => 'Demo',
            'last_name' => 'Applicant',
            'email' => 'applicant@example.com',
            'phone' => '0000000000',
            'location' => 'Amman',
            'gender' => 'female',
            'password' => 'Test-Password9!',
        ])->refresh();
    }
}
