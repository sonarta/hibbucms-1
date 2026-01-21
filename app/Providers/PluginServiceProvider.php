<?php

namespace App\Providers;

use App\Models\Plugin;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register plugins collection as singleton
        $this->app->singleton('plugins', function ($app) {
            try {
                if (!Schema::hasTable('plugins')) {
                    return collect();
                }

                return Plugin::getActive();
            } catch (\Exception $e) {
                return collect();
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        try {
            if (!Schema::hasTable('plugins')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        // Load active plugins
        $plugins = app('plugins');

        foreach ($plugins as $plugin) {
            $this->loadPlugin($plugin);
        }

        // Fire hook after all plugins are loaded
        do_action('plugins.loaded', $plugins);
    }

    /**
     * Load a single plugin
     */
    protected function loadPlugin(Plugin $plugin): void
    {
        $indexPath = $plugin->getIndexPath();

        if (!file_exists($indexPath)) {
            return;
        }

        // Fire hook before loading plugin
        do_action('plugin.loading', $plugin);

        try {
            require_once $indexPath;

            // Fire hook after loading plugin
            do_action('plugin.loaded', $plugin);
        } catch (\Exception $e) {
            // Log error but don't break the application
            \Log::error("Failed to load plugin {$plugin->name}: " . $e->getMessage());
        }
    }
}
