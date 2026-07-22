<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'content', 'cover_image', 'status',
        'is_pinned', 'published_at', 'target',
        'target_value', 'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Scope news that have been published.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Order pinned news first, then by publication date (most recent first).
     */
    public function scopePinnedFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
