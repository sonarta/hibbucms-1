<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DefaultThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Scans the themes directory and registers all themes with theme.json files.
     */
    public function run(): void
    {
        $themesPath = base_path('themes');

        if (!File::exists($themesPath)) {
            return;
        }

        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $folderName = basename($directory);
            $configPath = $directory . '/theme.json';

            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);

                if (!$config || !isset($config['name']) || !isset($config['slug']) || !isset($config['version'])) {
                    $this->command->warn("Skipping theme '{$folderName}': Invalid theme.json");
                    continue;
                }

                Theme::updateOrCreate(
                    ['folder_name' => $folderName],
                    [
                        'name' => $config['name'],
                        'slug' => $config['slug'],
                        'description' => $config['description'] ?? null,
                        'version' => $config['version'],
                        'author' => $config['author'] ?? null,
                        'settings' => $config['settings'] ?? null,
                        'preview' => $config['preview'] ?? null,
                    ]
                );

                $this->command->info("Registered theme: {$config['name']}");
            }
        }

        // Activate the first theme if none is active
        $activeTheme = Theme::where('is_active', true)->first();
        if (!$activeTheme) {
            $defaultTheme = Theme::where('slug', 'default')->first() ?? Theme::first();
            if ($defaultTheme) {
                $defaultTheme->update(['is_active' => true]);
                $this->command->info("Activated theme: {$defaultTheme->name}");
            }
        }
    }
}

