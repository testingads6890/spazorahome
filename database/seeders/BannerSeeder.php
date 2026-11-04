<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;
use Illuminate\Support\Str;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Summer Sale',
                'subtitle' => 'Up to 50% off on selected items',
                'image' => 'banners/summer-sale.jpg',
                'link' => '/search?query=summer',
                'position' => 'homepage',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'New Arrivals',
                'subtitle' => 'Latest collections just in',
                'image' => 'banners/new-arrivals.jpg',
                'link' => '/search?is_new_arrival=1',
                'position' => 'homepage',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Living Room Collection',
                'subtitle' => 'Modern furniture for your living space',
                'image' => 'banners/living-room.jpg',
                'link' => '/categories/living-room',
                'position' => 'category',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Bedroom Essentials',
                'subtitle' => 'Everything you need for a good night\'s sleep',
                'image' => 'banners/bedroom.jpg',
                'link' => '/categories/bedroom',
                'position' => 'category',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Free Shipping',
                'subtitle' => 'On all orders over ₹1000',
                'image' => 'banners/free-shipping.jpg',
                'link' => '/cart',
                'position' => 'promo-bar',
                'sort_order' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $bannerData) {
            Banner::firstOrCreate(
                ['title' => $bannerData['title']],
                $bannerData
            );
        }
    }
}