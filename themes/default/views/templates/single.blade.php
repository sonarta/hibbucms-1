{{--
Template Name: Single Post Template
Description: Custom template for single post page
--}}

@extends('theme::layouts.app')

@section('content')
    <div class="single-template">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-8">
                    <article class="post">
                        <header class="post-header mb-4">
                            <h1 class="post-title">{{ $post->title }}</h1>
                            <div class="post-meta">
                                <span class="post-date">
                                    <i class="fas fa-calendar-alt"></i> {{ $post->created_at->format('d M Y') }}
                                </span>
                                @if($post->author)
                                    <span class="post-author ms-3">
                                        <i class="fas fa-user"></i> {{ $post->author->name }}
                                    </span>
                                @endif
                                @if($post->categories->count() > 0)
                                    <span class="post-categories ms-3">
                                        <i class="fas fa-folder"></i>
                                        @foreach($post->categories as $category)
                                            <a href="{{ url('category/' . $category->slug) }}"
                                                class="category-link">{{ $category->name }}</a>{{ !$loop->last ? ', ' : '' }}
                                        @endforeach
                                    </span>
                                @endif
                            </div>
                        </header>

                        @if($post->featured_image)
                            <div class="post-featured-image mb-4">
                                <img src="{{ $post->featured_image }}" class="img-fluid rounded" alt="{{ $post->title }}">
                            </div>
                        @endif

                        <div class="post-content">
                            {!! apply_filters('post.content', $post->content, $post) !!}
                        </div>

                        @if($post->tags->count() > 0)
                            <div class="post-tags mt-4">
                                <h5>Tags:</h5>
                                <div class="tags">
                                    @foreach($post->tags as $tag)
                                        <a href="{{ url('tag/' . $tag->slug) }}"
                                            class="badge bg-secondary text-decoration-none me-1 mb-1">{{ $tag->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="post-share mt-4">
                            <h5>Share:</h5>
                            <div class="social-share">
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('blog/' . $post->slug)) }}"
                                    target="_blank" class="btn btn-primary me-2">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url('blog/' . $post->slug)) }}&text={{ urlencode($post->title) }}"
                                    target="_blank" class="btn btn-info me-2">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                                <a href="https://wa.me/?text={{ urlencode($post->title . ' ' . url('blog/' . $post->slug)) }}"
                                    target="_blank" class="btn btn-success">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    </article>

                    @if(isset($related_posts) && $related_posts->count() > 0)
                        <div class="related-posts mt-5">
                            <h3>Related Posts</h3>
                            <div class="row">
                                @foreach($related_posts as $related_post)
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            @if($related_post->featured_image)
                                                <img src="{{ $related_post->featured_image }}" class="card-img-top"
                                                    alt="{{ $related_post->title }}">
                                            @endif
                                            <div class="card-body">
                                                <h5 class="card-title">{{ $related_post->title }}</h5>
                                                <p class="card-text">{{ Str::limit($related_post->excerpt, 80) }}</p>
                                                <a href="{{ url('blog/' . $related_post->slug) }}"
                                                    class="btn btn-sm btn-primary">Baca Selengkapnya</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(isset($comments_enabled) && $comments_enabled)
                        <div class="comments-section mt-5">
                            <h3>Comments</h3>
                            <!-- Sistem komentar akan ditambahkan di sini -->
                        </div>
                    @endif
                </div>

                <div class="col-md-4">
                    <div class="sidebar">
                        @if(isset($recent_posts) && $recent_posts->count() > 0)
                            <div class="recent-posts mb-4">
                                <h3>Latest Posts</h3>
                                <ul class="list-group">
                                    @foreach($recent_posts as $recent_post)
                                        <li class="list-group-item">
                                            <a href="{{ url('blog/' . $recent_post->slug) }}">{{ $recent_post->title }}</a>
                                            <small
                                                class="text-muted d-block">{{ $recent_post->created_at->format('d M Y') }}</small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(isset($categories) && $categories->count() > 0)
                            <div class="categories mb-4">
                                <h3>Categories</h3>
                                <ul class="list-group">
                                    @foreach($categories as $category)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="{{ url('category/' . $category->slug) }}">{{ $category->name }}</a>
                                            <span class="badge bg-primary rounded-pill">{{ $category->posts_count }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(isset($tags) && $tags->count() > 0)
                            <div class="tags-widget mb-4">
                                <h3>Tags</h3>
                                <div class="tags">
                                    @foreach($tags as $tag)
                                        <a href="{{ url('tag/' . $tag->slug) }}"
                                            class="badge bg-secondary text-decoration-none me-1 mb-1">{{ $tag->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .post-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .post-title {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .post-meta {
            color: #6c757d;
        }

        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .post-content img {
            max-width: 100%;
            height: auto;
        }

        .category-link {
            color: #6c757d;
            text-decoration: none;
        }

        .category-link:hover {
            text-decoration: underline;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
        }

        .social-share {
            display: flex;
        }
    </style>
@endpush