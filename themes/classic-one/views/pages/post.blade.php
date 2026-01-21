@extends('theme::layouts.app')

@section('title', $post->title)
@section('meta_description', Str::limit(strip_tags($post->content), 160))
@section('meta_keywords', $post->tags->pluck('name')->join(', '))

@section('content')
    <article class="single-post">
        <!-- Post Header -->
        <header class="post-header">
            <h1 class="post-title">{{ $post->title }}</h1>

            <div class="post-meta">
                <span class="post-date">
                    <i class="fa fa-calendar"></i> {{ $post->published_at->format('F d, Y') }}
                </span>

                {{-- Plugin Meta Hook (Reading Time, etc) --}}
                {!! apply_filters('post.meta', '', $post) !!}

                @if ($post->category)
                    <span class="post-category">
                        <i class="fa fa-folder"></i>
                        <a href="{{ route('blog', ['category' => $post->category->slug]) }}">
                            {{ $post->category->name }}
                        </a>
                    </span>
                @endif
            </div>
        </header>

        <!-- Featured Image -->
        @if ($post->featured_image)
            <div class="post-thumbnail">
                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}">
            </div>
        @endif

        <!-- Post Content -->
        <div class="post-content">
            {!! apply_filters('post.content', $post->content, $post) !!}
        </div>

        <!-- Post Tags -->
        @if ($post->tags->count() > 0)
            <div class="post-tags">
                <h4><i class="fa fa-tags"></i> Tags:</h4>
                <div class="tags">
                    @foreach ($post->tags as $tag)
                        <a href="{{ route('blog', ['tag' => $tag->slug]) }}" class="tag-link">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Post Navigation -->
        <div class="post-navigation">
            <div class="nav-previous">
                @if (isset($previousPost))
                    <a href="{{ route('blog.post', $previousPost->slug) }}" rel="prev">
                        <span class="nav-subtitle"><i class="fa fa-arrow-left"></i> Previous Post</span>
                        <span class="nav-title">{{ $previousPost->title }}</span>
                    </a>
                @endif
            </div>

            <div class="nav-next">
                @if (isset($nextPost))
                    <a href="{{ route('blog.post', $nextPost->slug) }}" rel="next">
                        <span class="nav-subtitle">Next Post <i class="fa fa-arrow-right"></i></span>
                        <span class="nav-title">{{ $nextPost->title }}</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Related Posts -->
        @if ($relatedPosts->count() > 0)
            <div class="related-posts">
                <h3 class="section-title">Related Posts</h3>
                <div class="row">
                    @foreach ($relatedPosts as $relatedPost)
                        <div class="col-md-4">
                            <article class="related-post">
                                @if ($relatedPost->featured_image)
                                    <div class="post-thumbnail">
                                        <a href="{{ route('blog.post', $relatedPost->slug) }}">
                                            <img src="{{ asset('storage/' . $relatedPost->featured_image) }}"
                                                alt="{{ $relatedPost->title }}">
                                        </a>
                                    </div>
                                @endif

                                <h4 class="post-title">
                                    <a href="{{ route('blog.post', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                                </h4>

                                <div class="post-meta">
                                    <span class="post-date">
                                        {{ $relatedPost->published_at->format('M d, Y') }}
                                    </span>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </article>
@endsection

@push('styles')
    <style>
        .single-post .post-content {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .single-post .post-content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
        }

        .single-post .post-content h2,
        .single-post .post-content h3,
        .single-post .post-content h4 {
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .single-post .post-content p {
            margin-bottom: 20px;
        }

        .single-post .post-content blockquote {
            border-left: 4px solid #0066cc;
            padding-left: 20px;
            margin-left: 0;
            font-style: italic;
            color: #555;
        }
    </style>
@endpush
