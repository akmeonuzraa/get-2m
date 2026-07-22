<?php

use App\Models\Space;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'user@test.local',
        'password' => bcrypt('password'),
        'role' => 'user',
        'is_active' => true,
    ]);

    $this->space = Space::create([
        'name' => 'Test Space',
        'description' => 'A test space',
        'created_by' => $this->user->id,
    ]);

    $this->user->spaces()->attach($this->space->id, ['role' => 'contributor', 'joined_at' => now()]);
});

it('uploads a real pdf file', function (): void {
    $file = UploadedFile::fake()->createWithContent('document.pdf', "%PDF-1.4\n%EOF");

    $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
        'title' => 'Valid PDF Document',
        'description' => 'A test PDF document',
        'file' => $file,
        'space_id' => $this->space->id,
    ])->assertCreated();

    $this->assertDatabaseHas('documents', [
        'title' => 'Valid PDF Document',
        'original_filename' => 'document.pdf',
    ]);
});

it('rejects spoofed mime file', function (): void {
    $file = UploadedFile::fake()->createWithContent('fake.pdf', 'MZ\x90\x00' . str_repeat('A', 100));

    $this->actingAs($this->user, 'sanctum')->post('/api/documents', [
        'title' => 'Spoofed Executable',
        'file' => $file,
        'space_id' => $this->space->id,
    ])->assertUnprocessable();

    $this->assertDatabaseMissing('documents', ['title' => 'Spoofed Executable']);
});

it('rejects upload without auth', function (): void {
    $file = UploadedFile::fake()->createWithContent('document.pdf', '%PDF-1.4');

    $this->withHeader('Accept', 'application/json')
        ->post('/api/documents', [
            'title' => 'Unauthorized Upload',
            'file' => $file,
        ])->assertUnauthorized();
});
