<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Requests\Post\AutoSavePostRequest;
use App\Models\Post;
use App\Models\PostRevision;
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

        // Create a revision before updating
        $post->createRevision();

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

    /**
     * Auto-save post content.
     * Creates a new draft if no post exists, or updates existing post.
     */
    public function autoSave(AutoSavePostRequest $request, ?Post $post = null)
    {
        $validated = $request->validated();

        try {
            if ($post) {
                // Update existing post - only update fields that are provided
                $updateData = array_filter($validated, fn($value) => $value !== null);

                if (!empty($updateData)) {
                    $post->update($updateData);
                }

                if (isset($validated['tags'])) {
                    $post->tags()->sync($validated['tags']);
                }
            } else {
                // Create new draft post
                $validated['user_id'] = auth()->id();
                $validated['status'] = 'draft';
                $validated['slug'] = Str::slug($validated['title'] ?? 'untitled-' . time());

                // Set default title if not provided
                if (empty($validated['title'])) {
                    $validated['title'] = 'Untitled Draft';
                }

                // Set default content if not provided
                if (empty($validated['content'])) {
                    $validated['content'] = '';
                }

                // Set default category if not provided - required field
                if (empty($validated['category_id'])) {
                    $defaultCategory = Category::first();
                    if (!$defaultCategory) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No categories available. Please create a category first.',
                        ], 400);
                    }
                    $validated['category_id'] = $defaultCategory->id;
                }

                $post = Post::create($validated);

                if (isset($validated['tags'])) {
                    $post->tags()->sync($validated['tags']);
                }
            }

            return response()->json([
                'success' => true,
                'post_id' => $post->id,
                'saved_at' => now()->toISOString(),
                'message' => 'Draft saved successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Auto-save failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'validated' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of revisions for a post.
     */
    public function revisions(Post $post)
    {
        $revisions = $post->revisions()
            ->with('user:id,name')
            ->get()
            ->map(function ($revision) {
                return [
                    'id' => $revision->id,
                    'revision_number' => $revision->revision_number,
                    'title' => $revision->title,
                    'excerpt' => $revision->excerpt,
                    'content_preview' => Str::limit(strip_tags($revision->content), 200),
                    'user' => $revision->user?->name ?? 'Unknown',
                    'created_at' => $revision->created_at->format('d M Y H:i'),
                    'created_at_diff' => $revision->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Admin/Posts/Revisions', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
            ],
            'revisions' => $revisions,
        ]);
    }

    /**
     * Restore a post to a specific revision.
     */
    public function restoreRevision(Post $post, PostRevision $revision)
    {
        // Verify the revision belongs to this post
        if ($revision->post_id !== $post->id) {
            return back()->with('error', 'Invalid revision.');
        }

        // Create a revision of current state before restoring
        $post->createRevision();

        // Restore the post to the revision state
        $post->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'excerpt' => $revision->excerpt,
        ]);

        return redirect()->route('admin.posts.edit', $post)
            ->with('message', 'Post restored to revision #' . $revision->revision_number);
    }
}
