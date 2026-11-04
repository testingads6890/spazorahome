<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaticPage;

class StaticPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<p>Welcome to our Premium Interior Products Marketplace. We connect verified vendors with interior designers, architects, and retail customers while maintaining complete admin control over vendor onboarding and product publication.</p>',
                'meta_title' => 'About Us - Premium Interior Marketplace',
                'meta_description' => 'Learn about our mission to connect vendors with customers in the interior products marketplace.',
                'is_active' => true,
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => '<p>Get in touch with our support team for any questions or assistance.</p><p>Email: support@interiormarketplace.com</p><p>Phone: +91-9876543210</p>',
                'meta_title' => 'Contact Us - Premium Interior Marketplace',
                'meta_description' => 'Contact our support team for assistance.',
                'is_active' => true,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-conditions',
                'content' => '<p>Please read our terms and conditions carefully before using our marketplace.</p>',
                'meta_title' => 'Terms & Conditions - Premium Interior Marketplace',
                'meta_description' => 'Terms and conditions for using our marketplace.',
                'is_active' => true,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<p>We respect your privacy and are committed to protecting your personal data.</p>',
                'meta_title' => 'Privacy Policy - Premium Interior Marketplace',
                'meta_description' => 'Our privacy policy detailing how we handle your personal information.',
                'is_active' => true,
            ],
            [
                'title' => 'Shipping Policy',
                'slug' => 'shipping-policy',
                'content' => '<p>Information about our shipping partners and delivery timelines.</p>',
                'meta_title' => 'Shipping Policy - Premium Interior Marketplace',
                'meta_description' => 'Details about our shipping policy and delivery partners.',
                'is_active' => true,
            ],
            [
                'title' => 'Return Policy',
                'slug' => 'return-policy',
                'content' => '<p>Our hassle-free return policy ensures customer satisfaction.</p>',
                'meta_title' => 'Return Policy - Premium Interior Marketplace',
                'meta_description' => 'Information about our return and refund process.',
                'is_active' => true,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => '<p>Frequently asked questions about our marketplace.</p>',
                'meta_title' => 'FAQ - Premium Interior Marketplace',
                'meta_description' => 'Frequently asked questions about our marketplace.',
                'is_active' => true,
            ],
        ];

        foreach ($pages as $pageData) {
            StaticPage::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}