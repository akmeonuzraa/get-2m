<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'title', 'message',
        'notifiable_type', 'notifiable_id',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Scope notifications belonging to the given user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope notifications that have not been read yet.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Mark this notification as read.
     */
    public function markRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }
}
