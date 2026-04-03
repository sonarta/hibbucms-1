<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Pengaturan Umum
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'HibbuCMS',
                'type' => 'text',
                'description' => 'Nama Website',
                'is_public' => true,
            ],
            [
                'group' => 'general',
                'key' => 'site_description',
                'value' => 'Website CMS Modern dengan Laravel dan React',
                'type' => 'textarea',
                'description' => 'Deskripsi Website',
                'is_public' => true,
            ],
            [
                'group' => 'general',
                'key' => 'site_logo',
                'value' => null,
                'type' => 'text',
                'description' => 'URL Logo Website',
                'is_public' => true,
            ],
            [
                'group' => 'general',
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'text',
                'description' => 'URL Favicon Website',
                'is_public' => true,
            ],

            // Pengaturan SEO
            [
                'group' => 'seo',
                'key' => 'meta_title',
                'value' => 'HibbuCMS - Modern Content Management System',
                'type' => 'text',
                'description' => 'Meta Title',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'meta_description',
                'value' => 'CMS modern yang dibangun dengan Laravel dan React',
                'type' => 'textarea',
                'description' => 'Meta Description',
                'is_public' => true,
            ],
            [
                'group' => 'seo',
                'key' => 'meta_keywords',
                'value' => 'cms,laravel,react,modern cms',
                'type' => 'text',
                'description' => 'Meta Keywords (pisahkan dengan koma)',
                'is_public' => true,
            ],

            // Pengaturan Konten
            [
                'group' => 'content',
                'key' => 'posts_per_page',
                'value' => 10,
                'type' => 'number',
                'description' => 'Jumlah Post per Halaman',
                'is_public' => true,
            ],
            [
                'group' => 'content',
                'key' => 'excerpt_length',
                'value' => 150,
                'type' => 'number',
                'description' => 'Panjang Excerpt (karakter)',
                'is_public' => true,
            ],
            // Pengaturan Media Sosial
            [
                'group' => 'social',
                'key' => 'facebook_url',
                'value' => null,
                'type' => 'text',
                'description' => 'URL Facebook',
                'is_public' => true,
            ],
            [
                'group' => 'social',
                'key' => 'twitter_url',
                'value' => null,
                'type' => 'text',
                'description' => 'URL Twitter/X',
                'is_public' => true,
            ],
            [
                'group' => 'social',
                'key' => 'instagram_url',
                'value' => null,
                'type' => 'text',
                'description' => 'URL Instagram',
                'is_public' => true,
            ],

            // Pengaturan Email
            [
                'group' => 'email',
                'key' => 'mail_from_address',
                'value' => 'noreply@hibbu.com',
                'type' => 'text',
                'description' => 'Alamat Email Pengirim',
                'is_public' => false,
            ],
            [
                'group' => 'email',
                'key' => 'mail_from_name',
                'value' => 'HibbuCMS',
                'type' => 'text',
                'description' => 'Nama Pengirim Email',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key'], 'group' => $setting['group']],
                $setting
            );
        }
    }
}
