<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'folder_name',
        'description',
        'version',
        'author',
        'author_url',
        'is_active',
        'settings',
        'requires',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'requires' => 'array',
    ];

    /**
     * Get all active plugins
     */
    public static function getActive()
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Activate this plugin
     */
    public function activate(): self
    {
        $this->is_active = true;
        $this->save();

        return $this;
    }

    /**
     * Deactivate this plugin
     */
    public function deactivate(): self
    {
        $this->is_active = false;
        $this->save();

        return $this;
    }

    /**
     * Get the plugin's main file path
     */
    public function getIndexPath(): string
    {
        return base_path("plugins/{$this->folder_name}/index.php");
    }

    /**
     * Check if plugin's main file exists
     */
    public function hasValidStructure(): bool
    {
        return file_exists($this->getIndexPath());
    }
}
