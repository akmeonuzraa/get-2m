<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NewsTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $expected = [
            'title', 'content', 'cover_image', 'status',
            'is_pinned', 'published_at', 'target',
            'target_value', 'created_by',
        ];

        $this->assertSame($expected, (new News)->getFillable());
    }

    public function test_uses_soft_deletes(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(News::class));
    }

    public function test_is_pinned_is_cast_to_boolean(): void
    {
        $this->assertTrue((new News(['is_pinned' => 1]))->is_pinned);
        $this->assertFalse((new News(['is_pinned' => 0]))->is_pinned);
        $this->assertIsBool((new News(['is_pinned' => 1]))->is_pinned);
    }

    public function test_published_at_is_cast_to_carbon(): void
    {
        $news = new News(['published_at' => '2026-05-01 08:30:00']);

        $this->assertInstanceOf(Carbon::class, $news->published_at);
    }

    public function test_creator_relationship_uses_created_by_foreign_key(): void
    {
        $relation = (new News)->creator();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('created_by', $relation->getForeignKeyName());
    }

    public function test_comments_relationship(): void
    {
        $relation = (new News)->comments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Comment::class, $relation->getRelated());
    }
}
