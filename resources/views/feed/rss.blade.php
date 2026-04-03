{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ e($siteName) }}</title>
        <link>{{ url('/') }}</link>
        <description>{{ e($siteDescription) }}</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
        <atom:link href="{{ url('/feed.xml') }}" rel="self" type="application/rss+xml"/>
        @foreach($posts as $post)
            <item>
                <title>{{ e($post->title) }}</title>
                <link>{{ route('blog.post', $post->slug, absolute: true) }}</link>
                <guid isPermaLink="true">{{ route('blog.post', $post->slug, absolute: true) }}</guid>
                <pubDate>{{ $post->published_at?->toRssString() }}</pubDate>
                @if($post->excerpt)
                    <description><![CDATA[{!! $post->excerpt !!}]]></description>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
