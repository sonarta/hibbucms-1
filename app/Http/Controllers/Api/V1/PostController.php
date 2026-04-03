<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Published posts (public JSON API).
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 15), 50);

        $posts = Post::query()
            ->published()
            ->with(['category', 'tags', 'user:id,name', 'featuredImage'])
            ->latest('published_at')
            ->paginate($perPage);

        return PostResource::collection($posts);
    }

    /**
     * Single published post by slug.
     */
    public function show(Request $request, string $slug)
    {
        $post = Post::query()
            ->published()
            ->where('slug', $slug)
            ->with(['category', 'tags', 'user:id,name', 'featuredImage'])
            ->firstOrFail();

        return new PostResource($post);
    }
}
