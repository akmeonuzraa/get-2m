<?php

namespace Tests\Unit\Models;

use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class SpaceTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $this->assertSame(
            ['name', 'description', 'type', 'created_by'],
            (new Space)->getFillable()
        );
    }

    public function test_creator_relationship_uses_created_by_foreign_key(): void
    {
        $relation = (new Space)->creator();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('created_by', $relation->getForeignKeyName());
    }

    public function test_members_is_a_many_to_many_relationship_on_space_members(): void
    {
        $relation = (new Space)->members();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('space_members', $relation->getTable());
        $this->assertContains('role', $relation->getPivotColumns());
    }
}
