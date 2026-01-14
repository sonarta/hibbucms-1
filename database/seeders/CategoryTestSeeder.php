<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoryTestSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if exists to avoid clutter
        if (Category::where('slug', 'test-root')->exists()) return;

        $root = Category::create([
            'name' => 'Test Root',
            'slug' => 'test-root',
        ]);

        $child = Category::create([
            'name' => 'Test Child',
            'slug' => 'test-child',
            'parent_id' => $root->id,
        ]);

        $child->refresh();
        $root->refresh();

        if (!$child->isDescendantOf($root)) {
            throw new \Exception("Tree integrity check failed: Child is not descendant of Root.\nChild: LFT={$child->_lft}, RGT={$child->_rgt}\nRoot: LFT={$root->_lft}, RGT={$root->_rgt}");
        }

        $this->command->info("Nested Set Integrity Check Passed!");
    }
}
