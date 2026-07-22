<?php

namespace Tests\Unit\Models;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    public function test_fillable_attributes(): void
    {
        $expected = [
            'title', 'description', 'file_path', 'original_filename',
            'file_type', 'mime_type', 'file_size', 'keywords',
            'service', 'folder_id', 'space_id', 'uploaded_by',
            'current_version', 'status', 'trashed_at',
        ];

        $this->assertSame($expected, (new Document)->getFillable());
    }

    public function test_uses_soft_deletes(): void
    {
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Document::class));
    }

    public function test_keywords_is_cast_to_array(): void
    {
        $document = new Document(['keywords' => ['contract', 'legal']]);

        $this->assertIsArray($document->keywords);
        $this->assertSame(['contract', 'legal'], $document->keywords);
        $this->assertJson($document->getAttributes()['keywords']);
    }

    public function test_uploader_relationship_uses_uploaded_by_foreign_key(): void
    {
        $relation = (new Document)->uploader();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('uploaded_by', $relation->getForeignKeyName());
    }

    public function test_folder_relationship(): void
    {
        $relation = (new Document)->folder();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Folder::class, $relation->getRelated());
    }

    public function test_space_relationship(): void
    {
        $relation = (new Document)->space();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Space::class, $relation->getRelated());
    }

    public function test_versions_relationship(): void
    {
        $relation = (new Document)->versions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(DocumentVersion::class, $relation->getRelated());
    }
}
