<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCategoryFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_create_a_top_level_and_a_sub_category(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.categories.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Living', 'slug' => 'living', 'status' => 1, 'parent_id' => null])
            ->assertRedirect(route('admin.categories.index'));
        $parent = Category::where('slug', 'living')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Sofas', 'slug' => 'sofas', 'status' => 1, 'parent_id' => $parent->id])
            ->assertRedirect(route('admin.categories.index'));
        $this->assertSame($parent->id, Category::where('slug', 'sofas')->value('parent_id'));
    }

    public function test_edit_update_and_delete_a_category(): void
    {
        $admin = $this->admin();
        $cat = Category::create(['name' => 'Old', 'slug' => 'old', 'status' => 1]);

        $this->actingAs($admin)->get(route('admin.categories.edit', $cat))->assertOk()->assertSee('Old');
        $this->actingAs($admin)->put(route('admin.categories.update', $cat), ['name' => 'New', 'slug' => 'new', 'status' => 0, 'parent_id' => null])
            ->assertRedirect(route('admin.categories.index'));
        $this->assertSame('New', $cat->fresh()->name);
        $this->assertFalse((bool) $cat->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $cat))->assertRedirect(route('admin.categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $cat->id]);
    }

    public function test_a_category_cannot_point_to_a_missing_parent(): void
    {
        $this->actingAs($this->admin())->post(route('admin.categories.store'), ['name' => 'X', 'slug' => 'x', 'status' => 1, 'parent_id' => 999])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_category_picture_is_uploaded_replaced_and_removed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Lamps', 'slug' => 'lamps', 'status' => 1, 'parent_id' => null,
            'image_file' => \Illuminate\Http\UploadedFile::fake()->image('lamp.jpg', 400, 400),
        ])->assertRedirect(route('admin.categories.index'));
        $cat = Category::where('slug', 'lamps')->firstOrFail();
        $this->assertStringStartsWith('categories/', $cat->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($cat->image);
        $this->assertStringContainsString($cat->image, $cat->imageUrl());

        $this->actingAs($admin)->put(route('admin.categories.update', $cat), [
            'name' => 'Lamps', 'slug' => 'lamps', 'status' => 1, 'parent_id' => null,
            'image_file' => \Illuminate\Http\UploadedFile::fake()->image('new.png', 300, 300),
        ])->assertRedirect(route('admin.categories.index'));
        $first = $cat->image;
        $this->assertNotSame($first, $cat->fresh()->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($cat->fresh()->image);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($first);

        $this->actingAs($admin)->put(route('admin.categories.update', $cat), [
            'name' => 'Lamps', 'slug' => 'lamps', 'status' => 1, 'parent_id' => null, 'remove_image' => 1,
        ])->assertRedirect(route('admin.categories.index'));
        $this->assertNull($cat->fresh()->image);
        $this->assertNull($cat->fresh()->imageUrl());
    }

    public function test_the_list_shows_serial_number_category_id_and_image(): void
    {
        Category::create(['name' => 'Rugs', 'slug' => 'rugs', 'status' => true, 'image' => 'https://example.com/rug.jpg']);
        $html = $this->actingAs($this->admin())->get(route('admin.categories.index'))->assertOk()->getContent();
        $this->assertStringContainsString('<th>Sr. No.</th>', $html);
        $this->assertStringContainsString('<th>Category ID</th>', $html);
        $this->assertStringContainsString('https://example.com/rug.jpg', $html);
    }
}
