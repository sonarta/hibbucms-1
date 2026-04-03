<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class PageController extends Controller
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
        $query = Page::with(['user']);

        // Filter by search term
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('content', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $pages = $query->orderBy('order')->paginate(10);

        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', 'all')
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Pages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'featured_image' => 'nullable|image|max:2048',
            'meta_description' => 'nullable|max:255',
            'meta_keywords' => 'nullable|max:255',
            'status' => 'required|in:draft,published,pending_review',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('pages', 'public');
            $validated['featured_image'] = $path;
        }

        $validated['user_id'] = Auth::id();
        $validated['slug'] = str($request->title)->slug();

        Page::create($validated);

        return redirect()->route('admin.pages.index')
            ->with('message', 'Halaman berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page)
    {
        $page->load(['user']);

        return Inertia::render('Admin/Pages/Show', [
            'page' => $page
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page)
    {
        return Inertia::render('Admin/Pages/Edit', [
            'page' => $page,
            'preview' => [
                'url' => URL::route('preview.page', $page),
                'signed_url' => URL::temporarySignedRoute(
                    'preview.page',
                    now()->addHours(72),
                    ['page' => $page->id]
                ),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'featured_image' => 'nullable|image|max:2048',
            'meta_description' => 'nullable|max:255',
            'meta_keywords' => 'nullable|max:255',
            'status' => 'required|in:draft,published,pending_review',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('featured_image')) {
            if ($page->featured_image) {
                Storage::disk('public')->delete($page->featured_image);
            }
            $path = $request->file('featured_image')->store('pages', 'public');
            $validated['featured_image'] = $path;
        }

        $validated['slug'] = str($request->title)->slug();
        $page->update($validated);

        return redirect()->route('admin.pages.index')
            ->with('message', 'Halaman berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page)
    {
        if ($page->featured_image) {
            Storage::disk('public')->delete($page->featured_image);
        }

        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('message', 'Halaman berhasil dihapus.');
    }

    public function publish(Page $page)
    {
        $page->update([
            'status' => 'published'
        ]);

        return back()->with('message', 'Halaman berhasil dipublikasikan.');
    }

    public function unpublish(Page $page)
    {
        $page->update([
            'status' => 'draft'
        ]);

        return back()->with('message', 'Halaman berhasil dijadikan draft.');
    }
}
