<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpaceMember extends Model
{
    use HasFactory;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CONTRIBUTOR = 'contributor';
    public const ROLE_READER = 'reader';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_CONTRIBUTOR,
        self::ROLE_READER,
    ];

    protected $table = 'space_members';

    protected $fillable = [
        'space_id',
        'user_id',
        'role',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
