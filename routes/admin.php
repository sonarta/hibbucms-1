<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Settings\PasswordController;
use App\Http\Controllers\Admin\Settings\ProfileController;
use Inertia\Inertia;

Route::get('/admin', function () {
    return redirect()->route('admin.dashboard');
});

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Media Routes
    Route::get('media', [MediaController::class, 'index'])->name('admin.media.index');
    Route::post('media', [MediaController::class, 'store'])->name('admin.media.store');
    Route::get('media/{id}', [MediaController::class, 'show'])->name('admin.media.show');
    Route::delete('media/{id}', [MediaController::class, 'destroy'])->name('admin.media.destroy');
    Route::get('media/{id}/download', [MediaController::class, 'download'])->name('admin.media.download');
    Route::post('media/folders', [MediaController::class, 'storeFolder'])->name('admin.media.folders.store');
    Route::delete('media/folders/{id}', [MediaController::class, 'destroyFolder'])->name('admin.media.folders.destroy');
    Route::post('media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('admin.media.bulk-destroy');
    Route::post('media/move', [MediaController::class, 'move'])->name('admin.media.move');

    // Category Routes
    Route::resource('categories', CategoryController::class)->names([
        'index' => 'admin.categories.index',
        'create' => 'admin.categories.create',
        'store' => 'admin.categories.store',
        'show' => 'admin.categories.show',
        'edit' => 'admin.categories.edit',
        'update' => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy',
    ]);

    // Tag Routes
    Route::resource('tags', TagController::class)->names([
        'index' => 'admin.tags.index',
        'create' => 'admin.tags.create',
        'store' => 'admin.tags.store',
        'show' => 'admin.tags.show',
        'edit' => 'admin.tags.edit',
        'update' => 'admin.tags.update',
        'destroy' => 'admin.tags.destroy',
    ]);

    // Post Routes
    Route::resource('posts', PostController::class)->names([
        'index' => 'admin.posts.index',
        'create' => 'admin.posts.create',
        'store' => 'admin.posts.store',
        'show' => 'admin.posts.show',
        'edit' => 'admin.posts.edit',
        'update' => 'admin.posts.update',
        'destroy' => 'admin.posts.destroy',
    ]);
    Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('admin.posts.publish');
    Route::post('posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('admin.posts.unpublish');
    Route::post('posts/autosave', [PostController::class, 'autoSave'])->name('admin.posts.autosave');
    Route::post('posts/{post}/autosave', [PostController::class, 'autoSave'])->name('admin.posts.autosave.update');
    Route::get('posts/{post}/revisions', [PostController::class, 'revisions'])->name('admin.posts.revisions');
    Route::post('posts/{post}/revisions/{revision}/restore', [PostController::class, 'restoreRevision'])->name('admin.posts.revisions.restore');
    Route::post('posts/bulk-action', [PostController::class, 'bulkAction'])->name('admin.posts.bulk-action');

    // User Routes
    Route::resource('users', UserController::class)->names([
        'index' => 'admin.users.index',
        'create' => 'admin.users.create',
        'store' => 'admin.users.store',
        'show' => 'admin.users.show',
        'edit' => 'admin.users.edit',
        'update' => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    // Role Routes
    Route::resource('roles', RoleController::class)->except(['show'])->names([
        'index' => 'admin.roles.index',
        'create' => 'admin.roles.create',
        'store' => 'admin.roles.store',
        'edit' => 'admin.roles.edit',
        'update' => 'admin.roles.update',
        'destroy' => 'admin.roles.destroy',
    ]);

    // Page Routes
    Route::resource('pages', PageController::class)->names([
        'index' => 'admin.pages.index',
        'create' => 'admin.pages.create',
        'store' => 'admin.pages.store',
        'show' => 'admin.pages.show',
        'edit' => 'admin.pages.edit',
        'update' => 'admin.pages.update',
        'destroy' => 'admin.pages.destroy',
    ]);
    Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('admin.pages.publish');
    Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('admin.pages.unpublish');

    // Theme Management
    Route::prefix('themes')->group(function () {
        Route::get('/', [ThemeController::class, 'index'])->name('admin.themes.index');
        Route::post('/{theme}/activate', [ThemeController::class, 'activate'])->name('admin.themes.activate');
        Route::post('/scan', [ThemeController::class, 'scan'])->name('admin.themes.scan');
        Route::delete('/{theme}', [ThemeController::class, 'destroy'])->name('admin.themes.destroy');
        Route::post('/upload', [ThemeController::class, 'upload'])->name('admin.themes.upload');
    });

    // Plugin Management
    Route::prefix('plugins')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PluginController::class, 'index'])->name('admin.plugins.index');
        Route::post('/{plugin}/activate', [\App\Http\Controllers\Admin\PluginController::class, 'activate'])->name('admin.plugins.activate');
        Route::post('/{plugin}/deactivate', [\App\Http\Controllers\Admin\PluginController::class, 'deactivate'])->name('admin.plugins.deactivate');
        Route::post('/scan', [\App\Http\Controllers\Admin\PluginController::class, 'scan'])->name('admin.plugins.scan');
        Route::delete('/{plugin}', [\App\Http\Controllers\Admin\PluginController::class, 'destroy'])->name('admin.plugins.destroy');
        Route::post('/upload', [\App\Http\Controllers\Admin\PluginController::class, 'upload'])->name('admin.plugins.upload');
    });

    // Menu Routes
    Route::resource('menus', MenuController::class)->names([
        'index' => 'admin.menus.index',
        'create' => 'admin.menus.create',
        'store' => 'admin.menus.store',
        'show' => 'admin.menus.show',
        'edit' => 'admin.menus.edit',
        'update' => 'admin.menus.update',
        'destroy' => 'admin.menus.destroy',
    ]);
    Route::post('menus/{menu}/items', [MenuController::class, 'storeMenuItem'])->name('admin.menus.items.store');
    Route::put('menus/items/{menuItem}', [MenuController::class, 'updateMenuItem'])->name('admin.menus.items.update');
    Route::delete('menus/items/{menuItem}', [MenuController::class, 'destroyMenuItem'])->name('admin.menus.items.destroy');
    Route::post('menus/{menu}/reorder', [MenuController::class, 'reorderMenuItems'])->name('admin.menus.items.reorder');


    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.settings.general');
        });
        Route::get('/general', [SettingsController::class, 'general'])->name('admin.settings.general');
        Route::get('/seo', [SettingsController::class, 'seo'])->name('admin.settings.seo');
        Route::post('/cache/clear', [SettingsController::class, 'clearCache'])->name('admin.settings.clear-cache');

        // Route::redirect('settings', 'admin/settings/profile');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

        // This should be last to avoid conflicts with specific routes
        Route::put('/{group}', [SettingsController::class, 'update'])->name('admin.settings.update');

        Route::get('/appearance', function () {
            return Inertia::render('Admin/Settings/Appearance');
        })->name('appearance');
    });



});
