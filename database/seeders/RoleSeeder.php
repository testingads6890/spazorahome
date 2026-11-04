<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage categories', 'manage products', 'manage attributes', 'manage variants',
            'manage orders', 'manage customers', 'manage reviews', 'manage banners',
            'manage room ideas', 'manage shipments', 'view dashboard',
            'manage vendors', 'approve vendors', 'manage vendor products', 'manage vendor orders', 'manage vendor payouts',
            'manage support', 'manage reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $vendor = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $vendor->syncPermissions([
            'view dashboard',
            'manage products',
            'manage orders',
        ]);

        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $supportExecutive = Role::firstOrCreate(['name' => 'support_executive', 'guard_name' => 'web']);
        $supportExecutive->syncPermissions([
            'view dashboard',
            'manage support',
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );
        $user->assignRole($admin);
    }
}
