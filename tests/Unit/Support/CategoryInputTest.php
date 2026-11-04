<?php

namespace Tests\Unit\Support;

use App\Models\Category;
use App\Support\CategoryInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_blank_typed_name_keeps_the_picked_category(): void
    {
        $this->assertSame(7, CategoryInput::resolve(7, '  ', true));
        $this->assertNull(CategoryInput::resolve(null, null, true));
    }

    public function test_existing_name_is_reused_case_insensitively(): void
    {
        $sofas = Category::create(['name' => 'Sofas', 'slug' => 'sofas', 'status' => true]);

        $this->assertSame($sofas->id, CategoryInput::resolve(null, ' sofas ', false));
        $this->assertSame(1, Category::count());
    }

    public function test_new_category_from_admin_is_live_and_from_seller_awaits_approval(): void
    {
        $live = Category::find(CategoryInput::resolve(null, 'Bean Bags', true));
        $pending = Category::find(CategoryInput::resolve(null, 'Floor Lamps', false));

        $this->assertSame('bean-bags', $live->slug);
        $this->assertTrue((bool) $live->status);
        $this->assertFalse((bool) $pending->status);
    }

    public function test_slug_clash_gets_a_number(): void
    {
        Category::create(['name' => 'Other name', 'slug' => 'chairs', 'status' => true]);

        $this->assertSame('chairs-2', Category::find(CategoryInput::resolve(null, 'Chairs', true))->slug);
    }
}
