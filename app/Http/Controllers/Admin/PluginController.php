<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;

class PluginController extends Controller
{
    public function index()
    {
        $plugins = Plugin::all();

        return Inertia::render('Admin/Plugins/Index', [
            'plugins' => $plugins
        ]);
    }

    public function activate(Plugin $plugin)
    {
        if (!$plugin->hasValidStructure()) {
            return redirect()->back()->with('error', 'Plugin structure is invalid. Missing index.php file.');
        }

        $plugin->activate();

        return redirect()->back()->with('success', 'Plugin activated successfully');
    }

    public function deactivate(Plugin $plugin)
    {
        $plugin->deactivate();

        return redirect()->back()->with('success', 'Plugin deactivated successfully');
    }

    public function scan()
    {
        $pluginsPath = base_path('plugins');

        if (!File::exists($pluginsPath)) {
            File::makeDirectory($pluginsPath, 0755, true);
            return redirect()->back()->with('info', 'Plugins directory created. No plugins found.');
        }

        $directories = File::directories($pluginsPath);
        $registered = 0;

        foreach ($directories as $directory) {
            $folderName = basename($directory);
            $configPath = $directory . '/plugin.json';

            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);

                if (!$config || !isset($config['name']) || !isset($config['slug']) || !isset($config['version'])) {
                    continue;
                }

                Plugin::updateOrCreate(
                    ['folder_name' => $folderName],
                    [
                        'name' => $config['name'],
                        'slug' => $config['slug'],
                        'description' => $config['description'] ?? null,
                        'version' => $config['version'],
                        'author' => $config['author'] ?? null,
                        'author_url' => $config['author_url'] ?? null,
                        'settings' => $config['settings'] ?? null,
                        'requires' => $config['requires'] ?? null,
                    ]
                );

                $registered++;
            }
        }

        return redirect()->back()->with('success', "Plugins scanned successfully. {$registered} plugin(s) found.");
    }

    public function destroy(Plugin $plugin)
    {
        // Don't allow deleting active plugins
        if ($plugin->is_active) {
            return redirect()->back()->with('error', 'Cannot delete active plugin. Please deactivate it first.');
        }

        // Delete plugin folder if exists
        $pluginPath = base_path("plugins/{$plugin->folder_name}");
        if (File::exists($pluginPath)) {
            File::deleteDirectory($pluginPath);
        }

        // Delete from database
        $plugin->delete();

        return redirect()->back()->with('success', 'Plugin deleted successfully');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'plugin' => ['required', 'file', 'mimes:zip', 'max:10240'], // max 10MB
        ]);

        $zip = new ZipArchive;
        $file = $request->file('plugin');
        $pluginsPath = base_path('plugins');

        // Create temporary file for extraction
        $tempPath = storage_path('app/temp/' . uniqid());
        File::makeDirectory($tempPath, 0755, true);

        try {
            // Extract zip to temporary folder
            if ($zip->open($file->path()) === TRUE) {
                $zip->extractTo($tempPath);
                $zip->close();

                // Find plugin.json in root folder or first subfolder
                $pluginJson = null;
                $pluginFolder = null;

                // Check in root folder
                if (File::exists($tempPath . '/plugin.json')) {
                    $pluginJson = $tempPath . '/plugin.json';
                    $pluginFolder = $tempPath;
                } else {
                    // Check in first subfolder
                    $directories = File::directories($tempPath);
                    if (count($directories) > 0) {
                        $firstDir = $directories[0];
                        if (File::exists($firstDir . '/plugin.json')) {
                            $pluginJson = $firstDir . '/plugin.json';
                            $pluginFolder = $firstDir;
                        }
                    }
                }

                if (!$pluginJson) {
                    throw new \Exception('Invalid plugin structure: plugin.json not found');
                }

                // Read and validate plugin.json
                $config = json_decode(File::get($pluginJson), true);
                if (!isset($config['name']) || !isset($config['slug']) || !isset($config['version'])) {
                    throw new \Exception('Invalid plugin.json structure');
                }

                // Move to plugins folder
                $targetPath = $pluginsPath . '/' . $config['slug'];
                if (File::exists($targetPath)) {
                    File::deleteDirectory($targetPath);
                }
                File::moveDirectory($pluginFolder, $targetPath);

                // Register plugin in database
                Plugin::updateOrCreate(
                    ['folder_name' => $config['slug']],
                    [
                        'name' => $config['name'],
                        'slug' => $config['slug'],
                        'description' => $config['description'] ?? null,
                        'version' => $config['version'],
                        'author' => $config['author'] ?? null,
                        'author_url' => $config['author_url'] ?? null,
                        'settings' => $config['settings'] ?? null,
                        'requires' => $config['requires'] ?? null,
                    ]
                );

                return redirect()->back()->with('success', 'Plugin uploaded successfully');
            }

            throw new \Exception('Failed to open zip file');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to upload plugin: ' . $e->getMessage());
        } finally {
            // Clean up temporary files
            if (File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }
}
