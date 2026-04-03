<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Page;
use App\Services\TemplateHierarchy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class FrontendController extends Controller
{
    /**
     * Displaying home page
     * Using template hierarchy to find the appropriate template
     *
     * @return \Illuminate\View\View
     */
    public function home()
    {
        $posts = Post::with(['category', 'tags'])
            ->where('status', 'published')
            ->latest('published_at')
            ->take(6)
            ->get();

        $categories = Category::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        $tags = Tag::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        // Run action before rendering home page
        do_action('before_home_page', $posts, $categories, $tags);

        // Get data for view
        $data = [
            'posts' => $posts,
            'categories' => $categories,
            'tags' => $tags,
        ];

        // Apply filter for data
        $data = apply_filters('home_page_data', $data);

        // Find appropriate template based on hierarchy
        $templates = TemplateHierarchy::getHomeTemplates();
        $template = TemplateHierarchy::locateTemplate($templates, app('theme'));

        // If no template is found, use fallback
        if (!$template) {
            return view('theme::pages.home', $data);
        }

        return view($template, $data);
    }

    /**
     * Displaying blog/archive page
     * Supports filtering by category, tag, and search
     * Using template hierarchy to find the appropriate template
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function blog(Request $request)
    {
        $posts = Post::with(['category', 'tags'])
            ->where('status', 'published')
            ->when($request->category, function ($query, $category) {
                return $query->whereHas('category', function ($q) use ($category) {
                    $q->where('slug', $category);
                });
            })
            ->when($request->tag, function ($query, $tag) {
                return $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('slug', $tag);
                });
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            });

        // Apply filter for query posts
        $posts = apply_filters('blog_posts_query', $posts, $request);

        // Continue with pagination
        $posts = $posts->latest('published_at')
            ->paginate(apply_filters('blog_posts_per_page', 9))
            ->withQueryString();

        $categories = Category::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        $tags = Tag::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        // Run action before rendering blog page
        do_action('before_blog_page', $posts, $categories, $tags, $request);

        // Get data for view
        $data = [
            'posts' => $posts,
            'categories' => $categories,
            'tags' => $tags,
        ];

        // Apply filter for data
        $data = apply_filters('blog_page_data', $data, $request);

        // Determine template to be used based on request parameter
        $templates = [];

        if ($request->category) {
            // If category filter is applied, use category template
            $templates = TemplateHierarchy::getCategoryTemplates($request->category);
        } elseif ($request->tag) {
            // If tag filter is applied, use tag template
            $templates = TemplateHierarchy::getTagTemplates($request->tag);
        } elseif ($request->search) {
            // If search is applied, use search template
            $templates = TemplateHierarchy::getSearchTemplates();
        } else {
            // If no filter is applied, use blog/archive template
            $templates = TemplateHierarchy::getBlogTemplates();
        }

        // Find appropriate template based on hierarchy
        $template = TemplateHierarchy::locateTemplate($templates, app('theme'));

        // If no template is found, use fallback
        if (!$template) {
            return view('theme::pages.blog', $data);
        }

        return view($template, $data);
    }

    /**
     * Displaying single post page
     * Using template hierarchy to find the appropriate template
     *
     * @param string $slug Slug postingan
     * @return \Illuminate\View\View
     */
    public function post($slug)
    {
        $post = Post::with(['category', 'tags', 'user'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Run action after post is found
        do_action('post_found', $post);

        // Get previous post
        $previousPost = Post::where('status', 'published')
            ->where('published_at', '<', $post->published_at)
            ->orderBy('published_at', 'desc')
            ->first();

        // Get next post
        $nextPost = Post::where('status', 'published')
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at', 'asc')
            ->first();

        $relatedPosts = Post::with(['category', 'tags', 'user'])
            ->where('status', 'published')
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                return $query->where('category_id', $post->category_id)
                    ->orWhereHas('tags', function ($q) use ($post) {
                        $q->whereIn('id', $post->tags->pluck('id'));
                    });
            });

        // Apply filter for query related posts
        $relatedPosts = apply_filters('related_posts_query', $relatedPosts, $post);

        // Continue with data retrieval
        $relatedPosts = $relatedPosts->latest('published_at')
            ->take(apply_filters('related_posts_count', 3))
            ->get();

        // Get categories for sidebar
        $categories = Category::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        // Get recent posts for sidebar
        $recentPosts = Post::with(['category', 'user'])
            ->where('status', 'published')
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(5)
            ->get();

        // Get popular tags for sidebar
        $popularTags = Tag::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->orderBy('posts_count', 'desc')
            ->take(10)
            ->get();

        // Run action before rendering single post page
        do_action('before_single_post', $post, $relatedPosts);

        // Get data for view
        $data = [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'previousPost' => $previousPost,
            'nextPost' => $nextPost,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'popularTags' => $popularTags,
        ];

        // Apply filter for data
        $data = apply_filters('single_post_data', $data);

        // Find appropriate template based on hierarchy
        $templates = TemplateHierarchy::getSingleTemplates($slug);
        $template = TemplateHierarchy::locateTemplate($templates, app('theme'));

        // If no template is found, use fallback
        if (!$template) {
            return view('theme::pages.post', $data);
        }

        return view($template, $data);
    }

    /**
     * Displaying static page
     * Using template hierarchy to find the appropriate template
     *
     * @param string $slug Slug halaman
     * @return \Illuminate\View\View
     */
    public function page($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Run action after page is found
        do_action('page_found', $page);

        // Run action before rendering page
        do_action('before_page', $page);

        // Get data for view
        $data = [
            'page' => $page
        ];

        // Apply filter for data
        $data = apply_filters('page_data', $data);

        // Check if page has custom template
        $customTemplate = $page->template ?? null;

        if ($customTemplate) {
            // If page has custom template, try to use it
            $customTemplatePath = "theme::templates.{$customTemplate}";

            if (View::exists($customTemplatePath)) {
                return view($customTemplatePath, $data);
            }
        }

        // Find appropriate template based on hierarchy
        $templates = TemplateHierarchy::getPageTemplates($slug);
        $template = TemplateHierarchy::locateTemplate($templates, app('theme'));

        // If no template is found, use fallback
        if (!$template) {
            return view('theme::pages.page', $data);
        }

        return view($template, $data);
    }

    /**
     * Display custom static page with security validation
     * Uses dynamic theme system and template hierarchy
     * Only shows pages that have specific templates, otherwise returns 404
     *
     * @param string $page Page slug
     * @return \Illuminate\View\View
     */
    public function staticPage($page)
    {
        // Security: Validate page parameter to prevent path traversal attacks
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            abort(404, 'Invalid page format');
        }

        // Get current active theme dynamically instead of hardcoding
        $theme = app('theme');
        
        // Run action before rendering static page
        do_action('before_static_page', $page);

        // Prepare data for view
        $data = [
            'page_slug' => $page,
            'theme' => $theme
        ];

        // Apply filter for static page data
        $data = apply_filters('static_page_data', $data, $page);

        // Define template hierarchy for static pages (without generic fallbacks)
        // Only look for specific page templates, not generic ones
        $templates = [
            "pages.static.{$page}",
            "pages.{$page}",
            "static.{$page}"
        ];

        // Apply filter for template hierarchy
        $templates = apply_filters('static_page_templates', $templates, $page);

        // Find appropriate template using TemplateHierarchy service
        $template = TemplateHierarchy::locateTemplate($templates, $theme);

        // If template found, render it
        if ($template) {
            return view($template, $data);
        }

        // Fallback: try theme namespace for specific page only
        $fallbackPath = "theme::pages.static.{$page}";
        if (view()->exists($fallbackPath)) {
            return view($fallbackPath, $data);
        }

        // If no specific template found, return 404
        // This ensures only pages with actual templates are displayed
        abort(404, "Static page not found: {$page}");
    }

    /**
     * Preview a post (draft, scheduled, pending_review, or published) using theme templates.
     */
    public function previewPost(Post $post)
    {
        $post->load(['category', 'tags', 'user', 'featuredImage']);
        $postModel = $post;
        $postModel->setAttribute('featured_image', $postModel->featuredImage?->url);

        $previousPost = Post::where('status', 'published')
            ->where('published_at', '<', $postModel->published_at ?? now())
            ->orderBy('published_at', 'desc')
            ->first();

        $nextPost = Post::where('status', 'published')
            ->where('published_at', '>', $postModel->published_at ?? now())
            ->orderBy('published_at', 'asc')
            ->first();

        $relatedPosts = Post::with(['category', 'tags', 'user'])
            ->where('status', 'published')
            ->where('id', '!=', $postModel->id)
            ->where(function ($query) use ($postModel) {
                return $query->where('category_id', $postModel->category_id)
                    ->orWhereHas('tags', function ($q) use ($postModel) {
                        $q->whereIn('id', $postModel->tags->pluck('id'));
                    });
            });

        $relatedPosts = apply_filters('related_posts_query', $relatedPosts, $postModel);
        $relatedPosts = $relatedPosts->latest('published_at')
            ->take(apply_filters('related_posts_count', 3))
            ->get();

        $categories = Category::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        $recentPosts = Post::with(['category', 'user'])
            ->where('status', 'published')
            ->where('id', '!=', $postModel->id)
            ->latest('published_at')
            ->take(5)
            ->get();

        $popularTags = Tag::withCount(['posts' => function ($query) {
            $query->where('status', 'published');
        }])->orderBy('posts_count', 'desc')
            ->take(10)
            ->get();

        do_action('before_single_post', $postModel, $relatedPosts);

        $data = [
            'post' => $postModel,
            'relatedPosts' => $relatedPosts,
            'previousPost' => $previousPost,
            'nextPost' => $nextPost,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'popularTags' => $popularTags,
            'is_preview' => true,
        ];

        $data = apply_filters('single_post_data', $data);

        $templates = TemplateHierarchy::getSingleTemplates($postModel->slug);
        $template = TemplateHierarchy::locateTemplate($templates, app('theme'));

        if (! $template) {
            return view('theme::pages.post', $data);
        }

        return view($template, $data);
    }

    /**
     * Preview a page (draft, pending_review, or published) using theme templates.
     */
    public function previewPage(Page $page)
    {
        $pageModel = $page;

        do_action('page_found', $pageModel);
        do_action('before_page', $pageModel);

        $data = [
            'page' => $pageModel,
            'is_preview' => true,
        ];

        $data = apply_filters('page_data', $data);

        $customTemplate = $pageModel->template ?? null;

        if ($customTemplate) {
            $customTemplatePath = "theme::templates.{$customTemplate}";

            if (View::exists($customTemplatePath)) {
                return view($customTemplatePath, $data);
            }
        }

        $templates = TemplateHierarchy::getPageTemplates($pageModel->slug);
        $template = TemplateHierarchy::locateTemplate($templates, app('theme'));

        if (! $template) {
            return view('theme::pages.page', $data);
        }

        return view($template, $data);
    }
}
