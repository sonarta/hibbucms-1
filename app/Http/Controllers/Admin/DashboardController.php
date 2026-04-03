<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view dashboard')->only('index');
    }

    public function index()
    {
        // Get current month stats
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Posts stats
        $postsStats = [
            'current' => Post::where('created_at', '>=', $currentMonth)->count(),
            'last' => Post::where('created_at', '>=', $lastMonth)
                ->where('created_at', '<', $currentMonth)
                ->count(),
            'total' => Post::count(),
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
        ];

        // Categories stats
        $categoriesStats = [
            'current' => Category::where('created_at', '>=', $currentMonth)->count(),
            'last' => Category::where('created_at', '>=', $lastMonth)
                ->where('created_at', '<', $currentMonth)
                ->count(),
            'total' => Category::count(),
        ];

        // Tags stats
        $tagsStats = [
            'current' => Tag::where('created_at', '>=', $currentMonth)->count(),
            'last' => Tag::where('created_at', '>=', $lastMonth)
                ->where('created_at', '<', $currentMonth)
                ->count(),
            'total' => Tag::count(),
        ];

        // Media stats
        $mediaStats = [
            'current' => Media::where('created_at', '>=', $currentMonth)->count(),
            'last' => Media::where('created_at', '>=', $lastMonth)
                ->where('created_at', '<', $currentMonth)
                ->count(),
            'total' => Media::count(),
        ];

        // Calculate trends (percentage change from last month)
        $calculateTrend = function ($current, $last) {
            if ($last === 0) {
                return $current > 0 ? 100 : 0;
            }

            return round((($current - $last) / $last) * 100);
        };

        return Inertia::render('Dashboard', [
            'stats' => [
                'posts' => [
                    'total' => $postsStats['total'],
                    'published' => $postsStats['published'],
                    'draft' => $postsStats['draft'],
                    'trend' => $calculateTrend($postsStats['current'], $postsStats['last']),
                ],
                'categories' => [
                    'total' => $categoriesStats['total'],
                    'trend' => $calculateTrend($categoriesStats['current'], $categoriesStats['last']),
                ],
                'tags' => [
                    'total' => $tagsStats['total'],
                    'trend' => $calculateTrend($tagsStats['current'], $tagsStats['last']),
                ],
                'media' => [
                    'total' => $mediaStats['total'],
                    'trend' => $calculateTrend($mediaStats['current'], $mediaStats['last']),
                ],
            ],
            'recentPosts' => Post::with('user')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'status' => $post->status,
                    'created_at' => $post->created_at,
                    'author' => [
                        'name' => $post->user->name,
                    ],
                ]),
            'recentMedia' => Media::latest()
                ->take(8)
                ->get()
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'url' => $media->url,
                    'mime_type' => $media->mime_type,
                    'created_at' => $media->created_at,
                ]),
        ]);
    }
}
