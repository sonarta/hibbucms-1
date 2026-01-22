<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRevision extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'revision_number',
    ];

    /**
     * Get the post that owns this revision.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who created this revision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the next revision number for a post.
     */
    public static function getNextRevisionNumber(int $postId): int
    {
        $maxRevision = static::where('post_id', $postId)->max('revision_number');
        return ($maxRevision ?? 0) + 1;
    }
}
