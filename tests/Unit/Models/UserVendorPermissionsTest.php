<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserVendorPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): array
    {
        $owner = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $owner->id, 'business_name' => 'Shop', 'slug' => 'shop-' . $owner->id,
            'email' => "shop{$owner->id}@example.com", 'status' => 'approved', 'is_active' => true,
        ]);

        return [$owner, $vendor];
    }

    private function member(Vendor $vendor, string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        VendorUser::create(['vendor_id' => $vendor->id, 'user_id' => $user->id, 'role' => $role, 'permissions' => $permissions]);

        return $user->fresh();
    }

    public function test_owner_can_do_everything(): void
    {
        [$owner, $vendor] = $this->shop();
        $owner = $owner->fresh();

        $this->assertTrue($owner->isVendorOwner());
        $this->assertSame($vendor->id, $owner->vendorAccount()->id);
        foreach (['products', 'orders', 'payouts', 'owner'] as $area) {
            $this->assertTrue($owner->vendorCan($area), $area);
        }
    }

    public function test_manager_can_do_everything_but_owner_areas(): void
    {
        [, $vendor] = $this->shop();
        $manager = $this->member($vendor, 'manager');

        $this->assertFalse($manager->isVendorOwner());
        $this->assertSame($vendor->id, $manager->vendorAccount()->id);
        $this->assertTrue($manager->vendorCan('products'));
        $this->assertTrue($manager->vendorCan('payouts'));
        $this->assertFalse($manager->vendorCan('owner'));
        $this->assertFalse($manager->vendorCan('made-up-area'));
    }

    public function test_staff_only_get_ticked_areas(): void
    {
        [, $vendor] = $this->shop();
        $staff = $this->member($vendor, 'staff', ['orders', 'owner', 'bogus']);

        $this->assertTrue($staff->vendorCan('orders'));
        $this->assertFalse($staff->vendorCan('products'));
        $this->assertFalse($staff->vendorCan('owner'));
        $this->assertFalse($staff->vendorCan('bogus'));
        $this->assertSame(['orders'], $staff->vendorMembership->areas());
    }

    public function test_plain_customer_has_no_vendor_access(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->vendorAccount());
        $this->assertFalse($user->vendorCan('products'));
    }

    public function test_manager_areas_list_everything(): void
    {
        $this->assertSame(VendorUser::AREAS, (new VendorUser(['role' => 'manager']))->areas());
    }
}
