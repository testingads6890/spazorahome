<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
            BannerSeeder::class,
            StaticPageSeeder::class,
            CarriersTableSeeder::class,
            CurrencySeeder::class,
            LanguageSeeder::class,
        ]);
    }
}