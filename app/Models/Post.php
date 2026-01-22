<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image_id',
        'status',
        'published_at',
        'views',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->slug = $post->slug ?? Str::slug($post->title);

            if ($post->status === 'published' && !$post->published_at) {
                $post->published_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    protected $appends = ['featured_image_url'];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featuredImage?->url;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('published_at', '>', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get categories as a Collection for theme compatibility.
     * This wraps the single category relationship in a Collection
     * to maintain compatibility with theme code that expects multiple categories.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesAttribute()
    {
        $category = $this->category;
        return $category ? collect([$category]) : collect();
    }

    /**
     * Get revisions for this post.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->orderBy('revision_number', 'desc');
    }

    /**
     * Create a revision snapshot of the current post state.
     */
    public function createRevision(?int $userId = null): PostRevision
    {
        return $this->revisions()->create([
            'user_id' => $userId ?? auth()->id(),
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'revision_number' => PostRevision::getNextRevisionNumber($this->id),
        ]);
    }
}
