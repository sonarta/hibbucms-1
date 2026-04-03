<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;

class ThemeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage themes');
    }

    public function index()
    {
        $themes = Theme::all()->map(function ($theme) {
            // Tambahkan preview URL jika ada
            if ($theme->preview) {
                $theme->preview_url = asset("themes/{$theme->folder_name}/{$theme->preview}");
            }

            return $theme;
        });

        return Inertia::render('Admin/Themes/Index', [
            'themes' => $themes,
        ]);
    }

    public function activate(Theme $theme)
    {
        do_action('theme.before_activate', $theme);

        $theme->activate();

        do_action('theme.after_activate', $theme->fresh());

        return redirect()->back()->with('success', 'Theme activated successfully');
    }

    public function scan()
    {
        $themesPath = base_path('themes');
        $directories = File::directories($themesPath);

        foreach ($directories as $directory) {
            $folderName = basename($directory);
            $configPath = $directory.'/theme.json';

            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);

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
            }
        }

        return redirect()->back()->with('success', 'Themes scanned successfully');
    }

    public function destroy(Theme $theme)
    {
        // Jangan izinkan menghapus tema yang sedang aktif
        if ($theme->is_active) {
            return redirect()->back()->with('error', 'Cannot delete active theme');
        }

        do_action('theme.before_delete', $theme);

        $snapshot = [
            'id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'folder_name' => $theme->folder_name,
        ];

        // Hapus folder tema jika ada
        $themePath = base_path("themes/{$theme->folder_name}");
        if (File::exists($themePath)) {
            File::deleteDirectory($themePath);
        }

        // Hapus dari database
        $theme->delete();

        do_action('theme.after_delete', $snapshot);

        return redirect()->back()->with('success', 'Theme deleted successfully');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'theme' => ['required', 'file', 'mimes:zip', 'max:10240'], // max 10MB
        ]);

        $zip = new ZipArchive;
        $file = $request->file('theme');
        $themesPath = base_path('themes');

        // Buat temporary file untuk extract
        $tempPath = storage_path('app/temp/'.uniqid());
        File::makeDirectory($tempPath, 0755, true);

        try {
            // Extract zip ke temporary folder
            if ($zip->open($file->path()) === true) {
                $zip->extractTo($tempPath);
                $zip->close();

                // Cari theme.json di root folder atau subfolder pertama
                $themeJson = null;
                $themeFolder = null;

                // Cek di root folder
                if (File::exists($tempPath.'/theme.json')) {
                    $themeJson = $tempPath.'/theme.json';
                    $themeFolder = $tempPath;
                } else {
                    // Cek di subfolder pertama
                    $directories = File::directories($tempPath);
                    if (count($directories) > 0) {
                        $firstDir = $directories[0];
                        if (File::exists($firstDir.'/theme.json')) {
                            $themeJson = $firstDir.'/theme.json';
                            $themeFolder = $firstDir;
                        }
                    }
                }

                if (! $themeJson) {
                    throw new \Exception('Invalid theme structure: theme.json not found');
                }

                // Baca dan validasi theme.json
                $config = json_decode(File::get($themeJson), true);
                if (! isset($config['name']) || ! isset($config['slug']) || ! isset($config['version'])) {
                    throw new \Exception('Invalid theme.json structure');
                }

                // Pindahkan ke folder themes
                $targetPath = $themesPath.'/'.$config['slug'];
                if (File::exists($targetPath)) {
                    File::deleteDirectory($targetPath);
                }
                File::moveDirectory($themeFolder, $targetPath);

                // Daftarkan tema di database
                Theme::updateOrCreate(
                    ['folder_name' => $config['slug']],
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

                return redirect()->back()->with('success', 'Theme uploaded successfully');
            }

            throw new \Exception('Failed to open zip file');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to upload theme: '.$e->getMessage());
        } finally {
            // Bersihkan temporary files
            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }
}
