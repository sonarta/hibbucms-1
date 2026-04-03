<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'published', 'scheduled', 'pending_review') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE pages MODIFY COLUMN status ENUM('draft', 'published', 'pending_review') NOT NULL DEFAULT 'draft'");
        } elseif ($driver === 'sqlite') {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('status', 32)->default('draft')->change();
            });
            Schema::table('pages', function (Blueprint $table) {
                $table->string('status', 32)->default('draft')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('posts')->where('status', 'pending_review')->update(['status' => 'draft']);
        DB::table('pages')->where('status', 'pending_review')->update(['status' => 'draft']);

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'published', 'scheduled') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE pages MODIFY COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'draft'");
        } elseif ($driver === 'sqlite') {
            Schema::table('posts', function (Blueprint $table) {
                $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft')->change();
            });
            Schema::table('pages', function (Blueprint $table) {
                $table->enum('status', ['draft', 'published'])->default('draft')->change();
            });
        }
    }
};
