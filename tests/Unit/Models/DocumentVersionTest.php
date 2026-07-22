<?php

namespace Tests\Unit\Models;

use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class DocumentVersionTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $expected = [
            'document_id', 'file_path', 'original_filename',
            'file_size', 'version_number', 'change_note', 'uploaded_by',
        ];

        $this->assertSame($expected, (new DocumentVersion)->getFillable());
    }

    public function test_does_not_use_soft_deletes(): void
    {
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(DocumentVersion::class));
    }

    public function test_uploader_relationship_uses_uploaded_by_foreign_key(): void
    {
        $relation = (new DocumentVersion)->uploader();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('uploaded_by', $relation->getForeignKeyName());
    }
}
