<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\EnsureContentPreviewAllowed;

// Frontend Routes
Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/feed.xml', [FeedController::class, 'rss'])->name('feed.rss');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/preview/post/{post}', [FrontendController::class, 'previewPost'])
    ->name('preview.post')
    ->middleware(EnsureContentPreviewAllowed::class);
Route::get('/preview/page/{page}', [FrontendController::class, 'previewPage'])
    ->name('preview.page')
    ->middleware(EnsureContentPreviewAllowed::class);

Route::post('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'id'], true)) {
        abort(400);
    }
    session(['locale' => $locale]);

    return back();
})->name('locale.switch');
Route::get('/blog', [FrontendController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [FrontendController::class, 'post'])->name('blog.post');

// Frontend Page Route (specific routes first)
Route::get('/pages/{slug}', [FrontendController::class, 'page'])->name('page');

// Route untuk mengakses asset tema
Route::get('themes/{theme}/{path}', function ($theme, $path) {
    // Security: Validate theme name and path to prevent path traversal attacks
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
        abort(404, 'Invalid theme name');
    }

    // Security: Prevent path traversal by checking for '..' and absolute path patterns
    if (preg_match('/\.\./', $path) || str_starts_with($path, '/')) {
        abort(403, 'Access denied');
    }

    $filePath = base_path("themes/{$theme}/{$path}");

    // Security: Verify the resolved path is still within the themes directory
    $realPath = realpath($filePath);
    $themesDir = realpath(base_path('themes'));

    if (!$realPath || !$themesDir || !str_starts_with($realPath, $themesDir)) {
        abort(404);
    }

    if (!File::exists($filePath)) {
        abort(404);
    }

    $mimeType = File::mimeType($filePath);

    return Response::file($filePath, [
        'Content-Type' => $mimeType
    ]);
})->where('path', '.*');




// Import other route files first
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';


// Static page route (catch-all, must be last)
// Security: Only allow alphanumeric, underscore, and hyphen characters
Route::get('/{page}', [FrontendController::class, 'staticPage'])
    ->name('static.page')
    ->where('page', '[a-zA-Z0-9_-]+');
