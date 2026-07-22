<?php

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $expected = [
            'user_id', 'type', 'title', 'message',
            'notifiable_type', 'notifiable_id',
            'is_read', 'read_at',
        ];

        $this->assertSame($expected, (new Notification)->getFillable());
    }

    public function test_is_read_is_cast_to_boolean(): void
    {
        $this->assertTrue((new Notification(['is_read' => 1]))->is_read);
        $this->assertFalse((new Notification(['is_read' => 0]))->is_read);
        $this->assertIsBool((new Notification(['is_read' => 1]))->is_read);
    }

    public function test_read_at_is_cast_to_carbon(): void
    {
        $notification = new Notification(['read_at' => '2026-03-03 12:00:00']);

        $this->assertInstanceOf(Carbon::class, $notification->read_at);
    }

    public function test_user_relationship(): void
    {
        $relation = (new Notification)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('user_id', $relation->getForeignKeyName());
    }

    public function test_notifiable_is_a_morph_to_relationship(): void
    {
        $relation = (new Notification)->notifiable();

        $this->assertInstanceOf(MorphTo::class, $relation);
    }
}
