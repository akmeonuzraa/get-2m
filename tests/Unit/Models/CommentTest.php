<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CommentTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $this->assertSame(['news_id', 'user_id', 'content'], (new Comment)->getFillable());
    }

    public function test_uses_soft_deletes(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Comment::class));
    }

    public function test_user_relationship(): void
    {
        $relation = (new Comment)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_news_relationship(): void
    {
        $relation = (new Comment)->news();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(News::class, $relation->getRelated());
        $this->assertSame('news_id', $relation->getForeignKeyName());
    }
}
