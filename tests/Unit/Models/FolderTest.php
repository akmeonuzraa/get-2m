<?php

namespace Tests\Unit\Models;

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class FolderTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $this->assertSame(
            ['name', 'space_id', 'parent_id', 'created_by'],
            (new Folder)->getFillable()
        );
    }

    public function test_uses_soft_deletes(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Folder::class));
    }

    public function test_parent_relationship_is_self_referencing(): void
    {
        $relation = (new Folder)->parent();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Folder::class, $relation->getRelated());
        $this->assertSame('parent_id', $relation->getForeignKeyName());
    }

    public function test_children_relationship_is_self_referencing(): void
    {
        $relation = (new Folder)->children();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Folder::class, $relation->getRelated());
        $this->assertSame('parent_id', $relation->getForeignKeyName());
    }

    public function test_documents_relationship(): void
    {
        $relation = (new Folder)->documents();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Document::class, $relation->getRelated());
    }

    public function test_creator_relationship_uses_created_by_foreign_key(): void
    {
        $relation = (new Folder)->creator();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('created_by', $relation->getForeignKeyName());
    }
}
