<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a vendor user if it doesn't exist
        $vendorUser = User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Vendor User',
                'password' => Hash::make('password'),
                'account_type' => 'customer', // Vendors start as customers
            ]
        );

        // Without the vendor role this account could never open the seller panel.
        $vendorUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']));

        // Create vendor profile for the user
        Vendor::firstOrCreate(
            ['user_id' => $vendorUser->id],
            [
                'business_name' => 'Sample Vendor Store',
                'slug' => 'sample-vendor-store',
                'email' => 'vendor@example.com',
                'phone' => '9876543210',
                'address' => '123 Vendor Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'postal_code' => '400001',
                'tax_number' => '27ABCDE1234F1Z5',
                'bank_details' => json_encode([
                    'account_name' => 'Vendor User',
                    'account_number' => '1234567890',
                    'ifsc' => 'SBIN0001234',
                    'bank_name' => 'State Bank of India'
                ]),
                'commission_rate' => 15.00,
                'status' => 'approved',
                'is_active' => true,
                'approved_at' => now(),
            ]
        );
    }
}