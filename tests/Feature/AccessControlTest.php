<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'user'): User
    {
        return User::create([
            'name' => 'U'.uniqid(),
            'email' => uniqid().'@example.test',
            'password' => bcrypt('password123'),
            'role' => $role,
            'service' => 'DSI',
            'is_active' => true,
        ]);
    }

    private function makeDocument(User $owner, ?int $spaceId = null): Document
    {
        return Document::create([
            'title' => 'Secret',
            'file_path' => 'documents/secret.pdf',
            'original_filename' => 'secret.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'service' => 'DSI',
            'space_id' => $spaceId,
            'uploaded_by' => $owner->id,
            'current_version' => 1,
            'status' => 'active',
        ]);
    }

    public function test_stranger_cannot_view_others_document(): void
    {
        $owner = $this->makeUser();
        $document = $this->makeDocument($owner);

        Sanctum::actingAs($this->makeUser());

        $this->getJson("/api/documents/{$document->id}")->assertStatus(403);
    }

    public function test_stranger_cannot_permanently_delete_others_document(): void
    {
        $owner = $this->makeUser();
        $document = $this->makeDocument($owner);

        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/documents/{$document->id}")->assertStatus(403);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_uploader_can_view_and_delete_own_document(): void
    {
        $owner = $this->makeUser();
        $document = $this->makeDocument($owner);

        Sanctum::actingAs($owner);

        $this->getJson("/api/documents/{$document->id}")->assertStatus(200);
        $this->deleteJson("/api/documents/{$document->id}")->assertStatus(200);
    }

    public function test_space_member_can_view_space_document(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();

        $space = Space::create([
            'name' => 'RH',
            'type' => 'private',
            'created_by' => $owner->id,
        ]);
        $space->members()->attach($member->id, ['role' => 'lecteur']);

        $document = $this->makeDocument($owner, $space->id);

        Sanctum::actingAs($member);

        $this->getJson("/api/documents/{$document->id}")->assertStatus(200);
    }

    public function test_admin_can_delete_any_document(): void
    {
        $owner = $this->makeUser();
        $document = $this->makeDocument($owner);

        Sanctum::actingAs($this->makeUser('admin'));

        $this->deleteJson("/api/documents/{$document->id}")->assertStatus(200);
    }

    public function test_stranger_cannot_delete_others_folder(): void
    {
        $owner = $this->makeUser();

        $space = Space::create([
            'name' => 'RH',
            'type' => 'private',
            'created_by' => $owner->id,
        ]);

        $folder = Folder::create([
            'name' => 'Confidential',
            'space_id' => $space->id,
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($this->makeUser());

        $this->deleteJson("/api/folders/{$folder->id}")->assertStatus(403);
        $this->assertDatabaseHas('folders', ['id' => $folder->id]);
    }
}
