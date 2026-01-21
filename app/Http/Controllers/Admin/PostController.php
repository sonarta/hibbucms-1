<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Media;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view content')->only(['index', 'show']);
        $this->middleware('permission:create content')->only(['create', 'store']);
        $this->middleware('permission:edit content')->only(['edit', 'update']);
        $this->middleware('permission:delete content')->only('destroy');
        $this->middleware('permission:publish content')->only(['publish', 'unpublish']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Post::with(['user', 'category', 'tags', 'featuredImage']);

        // Filter by search term
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('excerpt', 'like', '%' . $request->search . '%')
                ->orWhere('content', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        $posts = $query->latest()->paginate(25);

        // Transform posts data to include featured image URL
        $posts->through(function ($post) {
            $post->featured_image = $post->featuredImage?->url;
            return $post;
        });

        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all'),
                'category' => $request->input('category', 'all')
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::defaultOrder()->get()->toTree();
        $tags = Tag::all();
        $media = Media::latest()->get();

        return Inertia::render('Admin/Posts/Create', [
            'categories' => $categories,
            'tags' => $tags,
            'media' => $media
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('featured_image')) {
            try {
                $media = Media::upload($request->file('featured_image'));
                $validated['featured_image_id'] = $media->id;
            } catch (\Exception $e) {
                return back()->withErrors(['featured_image' => 'Gagal mengupload gambar: ' . $e->getMessage()]);
            }
        } elseif ($request->filled('featured_image_id')) {
            $validated['featured_image_id'] = $request->input('featured_image_id');
        }

        $post = Post::create($validated);

        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return redirect()->route('admin.posts.index')
            ->with('message', 'Post created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        $post->load(['user', 'category', 'tags']);

        return Inertia::render('Admin/Posts/Show', [
            'post' => $post
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        $post->load(['tags', 'featuredImage']);
        $categories = Category::defaultOrder()->get()->toTree();
        $tags = Tag::all();
        $media = Media::latest()->get();

        return Inertia::render('Admin/Posts/Edit', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
            'media' => $media
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['title']);

        if ($request->hasFile('featured_image')) {
            try {
                $media = Media::upload($request->file('featured_image'));
                $validated['featured_image_id'] = $media->id;
            } catch (\Exception $e) {
                return back()->withErrors(['featured_image' => 'Gagal mengupload gambar: ' . $e->getMessage()]);
            }
        } elseif ($request->filled('featured_image_id')) {
            $validated['featured_image_id'] = $request->input('featured_image_id');
        }

        $post->update($validated);

        if (isset($validated['tags'])) {
            $post->tags()->sync($validated['tags']);
        }

        return redirect()->route('admin.posts.index')
            ->with('message', 'Post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Load the featured image relationship if not already loaded
        $post->load('featuredImage');

        // Delete the featured image file if it exists
        // Note: We don't delete the Media record since it might be used by other posts
        // If you want to delete orphaned media, consider a separate cleanup command

        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('message', 'Post deleted successfully.');
    }

    public function publish(Post $post)
    {
        $post->update([
            'status' => 'published',
            'published_at' => now()
        ]);

        return back()->with('message', 'Post published successfully.');
    }

    public function unpublish(Post $post)
    {
        $post->update([
            'status' => 'draft',
            'published_at' => null
        ]);

        return back()->with('message', 'Post unpublished successfully.');
    }
}
