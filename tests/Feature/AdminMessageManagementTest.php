<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMessageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_search_and_filter_messages(): void
    {
        $sender = User::factory()->create(['first_name' => 'Lina', 'last_name' => 'Saleh']);
        ContactMessage::factory()->create(['sender_id' => $sender->id, 'subject' => 'Booking question']);
        $other = User::factory()->create(['first_name' => 'Omar', 'last_name' => 'Haddad', 'email' => 'other-sender@example.test']);
        ContactMessage::factory()->create(['sender_id' => $other->id, 'subject' => 'Payment issue', 'status' => 'reviewed', 'reviewed_at' => now()]);
        ContactMessage::factory()->count(16)->create(['sender_id' => $other->id, 'subject' => 'General question']);

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.messages.index'))->assertOk()
            ->assertViewHas('messages', function ($messages) {
                return $messages->total() === 18 && $messages->hasMorePages();
            });

        $this->get(route('admin.messages.index', ['q' => 'Lina']))->assertOk()
            ->assertViewHas('messages', function ($messages) {
                return $messages->total() === 1 && $messages->first()->subject === 'Booking question';
            });

        $this->get(route('admin.messages.index', ['status' => 'reviewed']))->assertOk()
            ->assertViewHas('messages', function ($messages) {
                return $messages->total() === 1 && $messages->first()->subject === 'Payment issue';
            });

        $this->get(route('admin.messages.index', ['status' => 'wrong']))->assertSessionHasErrors('status');
    }

    public function test_admin_can_view_and_mark_a_message_as_reviewed(): void
    {
        $message = ContactMessage::factory()->create(['subject' => 'Need help', 'message' => 'Please call me']);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.messages.show', $message))->assertOk()
            ->assertSee('Need help')
            ->assertSee('Please call me')
            ->assertSee('تعليم كمراجَعة');

        $this->patch(route('admin.messages.update', $message))
            ->assertRedirect(route('admin.messages.show', $message))
            ->assertSessionHas('success');

        $message->refresh();
        $this->assertSame('reviewed', $message->status);
        $this->assertNotNull($message->reviewed_at);

        $this->patch(route('admin.messages.update', $message))->assertSessionHas('error');
        $this->get(route('admin.messages.show', $message))->assertOk()->assertDontSee('تعليم كمراجَعة');
    }

    public function test_non_admins_cannot_access_messages(): void
    {
        $message = ContactMessage::factory()->create();

        $this->get(route('admin.messages.index'))->assertRedirect(route('login'));

        foreach ([User::factory()->create(), User::factory()->vendor()->create()] as $user) {
            $this->actingAs($user);
            $this->get(route('admin.messages.index'))->assertForbidden();
            $this->get(route('admin.messages.show', $message))->assertForbidden();
            $this->patch(route('admin.messages.update', $message))->assertForbidden();
        }

        $this->assertSame('pending', $message->fresh()->status);
    }
}
