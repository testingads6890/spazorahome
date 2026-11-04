<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Every parameterless GET page in each panel must render for its role (empty database). */
class PanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public static function panels(): array
    {
        return [['admin', 'admin.'], ['customer', 'customer.'], ['vendor', 'vendor.']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('panels')]
    public function test_panel_pages_render(string $role, string $prefix): void
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        if ($role === 'vendor') {
            Vendor::create(['user_id' => $user->id, 'business_name' => 'Acme', 'slug' => 'acme', 'email' => 'acme@example.com', 'status' => 'approved', 'is_active' => true]);
        }
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, $prefix) || ! in_array('GET', $route->methods()) || $route->parameterNames()) {
                continue;
            }
            if (str_ends_with($name, 'order-tracking.track')) {
                continue; // needs a tracking_number query parameter
            }
            try {
                $status = $this->withoutExceptionHandling()->actingAs($user)->get($route->uri())->getStatusCode();
                if ($status >= 400) {
                    $failures[] = "$status $name";
                }
            } catch (\Throwable $e) {
                $failures[] = "$name: " . strtok($e->getMessage(), "
");
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }
}
