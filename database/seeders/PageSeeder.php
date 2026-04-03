<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\User;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::role('Admin')->first();

        // About Us Page
        Page::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => "<h2>Welcome to Our Company</h2>
                <p>We are a passionate team dedicated to delivering high-quality solutions to our clients.</p>

                <h3>Our Mission</h3>
                <p>To provide innovative and sustainable solutions that help our clients achieve their goals while maintaining the highest standards of quality and customer service.</p>

                <h3>Our Vision</h3>
                <p>To become a leading provider of technology solutions, recognized for our excellence, innovation, and commitment to customer success.</p>

                <h3>Our Values</h3>
                <ul>
                    <li><strong>Innovation:</strong> We constantly seek new and better ways to serve our clients</li>
                    <li><strong>Quality:</strong> We maintain the highest standards in everything we do</li>
                    <li><strong>Integrity:</strong> We conduct our business with honesty and transparency</li>
                    <li><strong>Customer Focus:</strong> Our clients' success is our success</li>
                </ul>",
            'meta_description' => 'Learn more about our company, our mission, vision, and the values that drive us forward.',
            'meta_keywords' => 'about us, company profile, mission, vision, values',
            'status' => 'published',
            'order' => 1,
            'user_id' => $admin->id,
        ]);

        // Privacy Policy Page
        Page::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => "<h2>Privacy Policy</h2>
                <p>Last updated: " . now()->format('F d, Y') . "</p>

                <h3>1. Information We Collect</h3>
                <p>We collect information that you provide directly to us, including:</p>
                <ul>
                    <li>Name and contact information</li>
                    <li>Account credentials</li>
                    <li>Payment information</li>
                    <li>Communication preferences</li>
                </ul>

                <h3>2. How We Use Your Information</h3>
                <p>We use the information we collect to:</p>
                <ul>
                    <li>Provide and maintain our services</li>
                    <li>Process your transactions</li>
                    <li>Send you technical notices and support messages</li>
                    <li>Communicate with you about products, services, and events</li>
                </ul>

                <h3>3. Information Sharing</h3>
                <p>We do not sell or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
                <ul>
                    <li>With your consent</li>
                    <li>To comply with legal obligations</li>
                    <li>To protect our rights and interests</li>
                </ul>",
            'meta_description' => 'Our privacy policy explains how we collect, use, and protect your personal information.',
            'meta_keywords' => 'privacy policy, data protection, personal information, privacy',
            'status' => 'published',
            'order' => 2,
            'user_id' => $admin->id,
        ]);

        // Terms of Service Page
        Page::create([
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'content' => "<h2>Terms of Service</h2>
                <p>Last updated: " . now()->format('F d, Y') . "</p>

                <h3>1. Acceptance of Terms</h3>
                <p>By accessing and using our services, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>

                <h3>2. Use License</h3>
                <p>Permission is granted to temporarily access and use our services for personal, non-commercial purposes, subject to the following restrictions:</p>
                <ul>
                    <li>You must not modify or copy the materials</li>
                    <li>You must not use the materials for any commercial purpose</li>
                    <li>You must not attempt to decompile or reverse engineer any software</li>
                    <li>You must not remove any copyright or proprietary notations</li>
                </ul>

                <h3>3. Disclaimer</h3>
                <p>Our services are provided \"as is\". We make no warranties, expressed or implied, and hereby disclaim and negate all other warranties, including without limitation:</p>
                <ul>
                    <li>Implied warranties of merchantability</li>
                    <li>Fitness for a particular purpose</li>
                    <li>Non-infringement of intellectual property</li>
                </ul>",
            'meta_description' => 'Read our terms of service to understand the rules and regulations governing the use of our services.',
            'meta_keywords' => 'terms of service, terms and conditions, user agreement, legal',
            'status' => 'published',
            'order' => 3,
            'user_id' => $admin->id,
        ]);
    }
}
