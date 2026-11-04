<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Demo sign-ins for testing every panel. DEMO DATA ONLY: change these passwords before going live.
 *
 *   php artisan db:seed --class=DemoAccountsSeeder
 *
 * Admin and staff sign in at /admin/login, sellers at /vendor/login, customers at /customer/login.
 */
class DemoAccountsSeeder extends Seeder
{
    public const PASSWORD = 'Demo-ERMYoJa-26';

    /** email => [name, role] */
    public const ACCOUNTS = [
        'admin@example.com' => ['Admin', 'admin'],
        'vendor@example.com' => ['Demo Vendor', 'vendor'],
        'demo.customer@example.com' => ['Demo Customer', 'customer'],
        'support.exec@example.com' => ['Support Executive', 'support_executive'],
        'support.manager@example.com' => ['Support Manager', 'support_manager'],
        'orders.manager@example.com' => ['Order Manager', 'order_manager'],
        'vendors.manager@example.com' => ['Vendor Manager', 'vendor_manager'],
        'products.manager@example.com' => ['Product Manager', 'product_manager'],
    ];

    public function run(): void
    {
        foreach (['admin', 'vendor', 'customer', 'vendor_manager', 'product_manager', 'order_manager', 'support_manager', 'support_executive'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        foreach (self::ACCOUNTS as $email => [$name, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->name = $name;
                $user->account_type = 'customer';
            }
            $user->password = Hash::make(self::PASSWORD);
            $user->save();
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
