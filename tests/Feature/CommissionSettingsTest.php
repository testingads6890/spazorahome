<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommissionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function vendor(array $o = []): Vendor
    {
        $user = \App\Models\User::create(['name' => 'Seller', 'email' => uniqid() . '@shop.test', 'password' => bcrypt('x')]);

        return Vendor::create($o + [
            'user_id' => $user->id, 'business_name' => 'Acme', 'slug' => uniqid('acme'), 'email' => $user->email,
            'status' => 'approved', 'is_active' => true,
        ]);
    }

    public function test_default_commission_is_ten_percent_when_nothing_is_set(): void
    {
        $this->assertSame(10.0, Vendor::defaultCommissionRate());
        $this->assertSame(10.0, $this->vendor()->effectiveCommissionRate());
    }

    public function test_admin_can_change_the_default_and_sellers_without_their_own_rate_follow_it(): void
    {
        $seller = $this->vendor();
        $this->actingAs($this->admin())->put(route('admin.marketplace.default-commission.update'), ['default_commission_rate' => 12.5])
            ->assertSessionHasNoErrors();

        $this->assertSame(12.5, Vendor::defaultCommissionRate());
        $this->assertSame(12.5, $seller->fresh()->effectiveCommissionRate());
    }

    public function test_default_rejects_values_outside_zero_to_hundred(): void
    {
        $this->actingAs($this->admin())->put(route('admin.marketplace.default-commission.update'), ['default_commission_rate' => 150])
            ->assertSessionHasErrors('default_commission_rate');
        $this->assertSame(10.0, Vendor::defaultCommissionRate());
    }

    public function test_admin_can_set_a_rate_for_one_seller_and_clear_it_back_to_default(): void
    {
        $seller = $this->vendor();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.vendors.commission.update', $seller), ['commission_rate' => 8])->assertSessionHasNoErrors();
        $this->assertSame(8.0, $seller->fresh()->effectiveCommissionRate());

        Setting::set('default_commission_rate', '15', 'string');
        $this->assertSame(8.0, $seller->fresh()->effectiveCommissionRate(), 'own rate wins over default');

        $this->actingAs($admin)->put(route('admin.vendors.commission.update', $seller), ['commission_rate' => ''])->assertSessionHasNoErrors();
        $this->assertNull($seller->fresh()->commission_rate);
        $this->assertSame(15.0, $seller->fresh()->effectiveCommissionRate(), 'blank follows the default again');
    }

    public function test_seller_rate_must_be_between_zero_and_hundred(): void
    {
        $seller = $this->vendor();
        $this->actingAs($this->admin())->put(route('admin.vendors.commission.update', $seller), ['commission_rate' => -1])->assertSessionHasErrors('commission_rate');
        $this->actingAs($this->admin())->put(route('admin.vendors.commission.update', $seller), ['commission_rate' => 101])->assertSessionHasErrors('commission_rate');
    }

    public function test_marketplace_and_vendor_pages_show_the_commission_controls(): void
    {
        $seller = $this->vendor();
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.marketplace.index'))->assertOk()->assertSee('Default commission (%)');
        $this->actingAs($admin)->get(route('admin.vendors.show', $seller))->assertOk()->assertSee('Commission for this seller (%)')->assertSee('(default)');
    }
}
