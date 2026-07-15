<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.local',
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Create test space (requires created_by)
        $this->space = Space::create([
            'name' => 'Test Space',
            'description' => 'A test space',
            'created_by' => $this->user->id,
        ]);
        $this->user->spaces()->attach($this->space->id);
    }

    /**
     * Test: Upload a real PDF file succeeds
     */
    public function test_upload_valid_pdf_file_succeeds(): void
    {
        // Create a real PDF file (minimal PDF header)
        $pdfContent = "%PDF-1.4\n%EOF";
        $file = UploadedFile::fake()->createWithContent('document.pdf', $pdfContent);

        $response = $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
            'title' => 'Valid PDF Document',
            'description' => 'A test PDF document',
            'file' => $file,
            'space_id' => $this->space->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('documents', [
            'title' => 'Valid PDF Document',
            'original_filename' => 'document.pdf',
        ]);
    }

    /**
     * Test: Upload a valid ZIP/archive file succeeds
     */
    public function test_upload_valid_zip_file_succeeds(): void
    {
        // ZIP magic bytes + minimal content
        $zipContent = "PK\x03\x04" . str_repeat("\x00", 100);
        $file = UploadedFile::fake()->createWithContent('archive.zip', $zipContent);

        $response = $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
            'title' => 'Valid ZIP Archive',
            'file' => $file,
            'space_id' => $this->space->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('documents', [
            'title' => 'Valid ZIP Archive',
            'original_filename' => 'archive.zip',
        ]);
    }

    /**
     * Test: Upload a file with unsupported extension is rejected
     */
    public function test_upload_unsupported_extension_rejected(): void
    {
        // Create file with .exe extension
        $file = UploadedFile::fake()->createWithContent('malware.exe', 'MZ\x90\x00');

        $response = $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
            'title' => 'Malicious Executable',
            'file' => $file,
            'space_id' => $this->space->id,
        ]);

        // Validation error (415 or 422)
        $response->assertStatus(422);
        $this->assertDatabaseMissing('documents', [
            'title' => 'Malicious Executable',
        ]);
    }

    /**
     * Test: Upload a spoofed file (PDF name but EXE content) is rejected
     */
    public function test_upload_spoofed_mime_type_rejected(): void
    {
        // Create a file with .pdf extension but EXE content (MZ header = DOS/Windows executable)
        $file = UploadedFile::fake()->createWithContent('fake.pdf', 'MZ\x90\x00' . str_repeat('A', 100));

        $response = $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
            'title' => 'Spoofed Executable',
            'file' => $file,
            'space_id' => $this->space->id,
        ]);

        // MIME validation error
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Le type de fichier réel ne correspond pas à l\'extension fournie.',
            'error' => 'invalid_file_type'
        ]);
        $this->assertDatabaseMissing('documents', [
            'title' => 'Spoofed Executable',
        ]);
    }

    /**
     * Test: Upload without authentication is rejected
     */
    public function test_upload_without_authentication_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('document.pdf', '%PDF-1.4');

        $response = $this->post('/api/documents', [
            'title' => 'Unauthorized Upload',
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Upload with insufficient permissions is rejected
     */
    public function test_upload_to_unauthorized_space_rejected(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $otherSpace = Space::create([
            'name' => 'Other Space',
            'description' => 'A space the user is not member of',
            'created_by' => $admin->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('document.pdf', '%PDF-1.4');

        $response = $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
            'title' => 'Unauthorized Space Upload',
            'file' => $file,
            'space_id' => $otherSpace->id,
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Vous n\'êtes pas membre de cet espace.'
        ]);
    }

    /**
     * Test: Upload oversized file is rejected
     */
    public function test_upload_oversized_file_rejected(): void
    {
        // Create a file larger than 20 MB
        $file = UploadedFile::fake()->create('huge.pdf', 21000); // 21 MB

        $response = $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
            'title' => 'Oversized File',
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('documents', [
            'title' => 'Oversized File',
        ]);
    }
}
