{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($urls as $u)
        <url>
            <loc>{{ e($u['loc']) }}</loc>
            @if(!empty($u['lastmod']))
                <lastmod>{{ e($u['lastmod']) }}</lastmod>
            @endif
            @if(!empty($u['changefreq']))
                <changefreq>{{ e($u['changefreq']) }}</changefreq>
            @endif
            @if(!empty($u['priority']))
                <priority>{{ e($u['priority']) }}</priority>
            @endif
        </url>
    @endforeach
</urlset>
