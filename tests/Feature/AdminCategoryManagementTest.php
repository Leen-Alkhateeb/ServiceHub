<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_search_active_categories(): void
    {
        Category::factory()->create(['name' => 'سباكة']);
        Category::factory()->create(['name' => 'كهرباء']);
        Category::factory()->create(['name' => 'قسم مؤرشف', 'is_deleted' => true]);

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.categories'))
            ->assertOk()
            ->assertSee('سباكة')
            ->assertSee('كهرباء')
            ->assertDontSee('قسم مؤرشف');

        $this->get(route('admin.categories', ['q' => 'سباك']))
            ->assertOk()
            ->assertViewHas('categories', function ($categories) {
                return $categories->total() === 1 && $categories->first()->name === 'سباكة';
            });
    }

    public function test_admin_can_create_update_and_archive_a_category(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.categories.store'), [
            'name' => 'تكييف',
            'description' => 'صيانة وتركيب المكيفات',
            'image' => $this->fakeImage('ac.png'),
        ])->assertRedirect(route('admin.categories'))->assertSessionHas('success');

        $category = Category::where('name', 'تكييف')->firstOrFail();
        Storage::disk('public')->assertExists($category->image);
        $oldImage = $category->image;

        $this->put(route('admin.categories.update', $category), [
            'name' => 'تكييف وتبريد',
            'description' => 'وصف جديد',
            'image' => $this->fakeImage('new.png'),
        ])->assertRedirect(route('admin.categories'));

        $category->refresh();
        $this->assertSame('تكييف وتبريد', $category->name);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($category->image);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories'))
            ->assertSessionHas('success');

        $this->assertTrue((bool) $category->fresh()->is_deleted);
        $this->get(route('admin.categories.edit', $category))->assertNotFound();
    }

    public function test_category_validation_rejects_invalid_data(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Category::factory()->create(['name' => 'نجارة']);

        $this->post(route('admin.categories.store'), [
            'name' => 'نجارة',
            'description' => '',
            'image' => UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['name', 'description', 'image']);

        $this->assertSame(1, Category::where('name', 'نجارة')->count());
    }

    public function test_non_admins_cannot_manage_categories(): void
    {
        $category = Category::factory()->create();

        $this->get(route('admin.categories'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.categories'))->assertForbidden();
        $this->post(route('admin.categories.store'), [])->assertForbidden();
        $this->delete(route('admin.categories.destroy', $category))->assertForbidden();

        $this->assertFalse((bool) $category->fresh()->is_deleted);
    }

    private function fakeImage(string $name): UploadedFile
    {
        // A real 1x1 PNG, so the test does not need the GD extension.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
