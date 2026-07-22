<?php

use App\Models\Document;
use App\Models\Notification;
use App\Models\Space;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function actingUser(string $role = 'user', bool $active = true): User
{
    return User::factory()->create([
        'role' => $role,
        'is_active' => $active,
        'password' => Hash::make('password'),
    ]);
}

it('uses harmonized english roles for space members', function (): void {
    $admin = actingUser('admin');
    $member = actingUser('user');

    $space = Space::create([
        'name' => 'Produit',
        'created_by' => $admin->id,
    ]);
    $space->members()->attach($admin->id, ['role' => 'admin', 'joined_at' => now()]);

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/spaces/{$space->id}/members", [
            'user_id' => $member->id,
            'role' => 'contributor',
        ])
        ->assertCreated();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/spaces/{$space->id}/members/{$member->id}", ['role' => 'owner'])
        ->assertUnprocessable();
});

it('exposes joined_at on space member pivot without crashing', function (): void {
    expect(Schema::hasColumn('space_members', 'joined_at'))->toBeTrue();

    $admin = actingUser('admin');
    $user = actingUser();

    $space = Space::create([
        'name' => 'Espace RH',
        'created_by' => $admin->id,
    ]);
    $space->members()->attach($user->id, ['role' => 'reader', 'joined_at' => now()]);

    $loaded = $space->members()->first();
    expect($loaded)->not->toBeNull();
    expect($loaded->pivot->joined_at)->not->toBeNull();
});

it('applies api envelope globally on success and error', function (): void {
    $user = actingUser();

    $this->withHeader('Accept', 'application/json')
        ->get('/api/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 401);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', $user->email);
});

it('paginates listings through base crud controller', function (): void {
    $admin = actingUser('admin');
    User::factory()->count(25)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/users')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 15);
});

it('handles notification endpoints for authenticated user', function (): void {
    $user = actingUser();
    Notification::create([
        'user_id' => $user->id,
        'type' => 'space_activity',
        'title' => 'Nouveau document',
        'message' => 'Un document a été ajouté.',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.count', 1);

    $notificationId = Notification::query()->where('user_id', $user->id)->value('id');

    $this->actingAs($user, 'sanctum')
        ->patchJson("/api/notifications/{$notificationId}/read")
        ->assertOk()
        ->assertJsonPath('data.is_read', true);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/notifications/read-all')
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/notifications/{$notificationId}")
        ->assertOk();
});

it('blocks notifications routes for guests', function (): void {
    $this->getJson('/api/notifications')->assertUnauthorized();
});

it('manages spaces and members with proper access control', function (): void {
    $admin = actingUser('admin');
    $user = actingUser();

    $create = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/spaces', ['name' => 'Ops', 'type' => 'private'])
        ->assertCreated()
        ->json('data');

    $spaceId = $create['id'];

    $this->actingAs($admin, 'sanctum')
        ->postJson("/api/spaces/{$spaceId}/members", ['user_id' => $user->id, 'role' => 'reader'])
        ->assertCreated();

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/spaces/{$spaceId}/members/{$user->id}", ['role' => 'contributor'])
        ->assertOk();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/spaces/{$spaceId}/members/{$user->id}")
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/spaces/{$spaceId}")
        ->assertForbidden();
});

it('manages users and toggle active for admin only', function (): void {
    $admin = actingUser('admin');
    $regular = actingUser('user');

    $created = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/users', [
            'name' => 'Agent',
            'email' => 'agent@example.test',
            'password' => 'password123',
            'role' => 'user',
        ])
        ->assertCreated()
        ->json('data');

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/users/{$created['id']}/toggle-active")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->actingAs($regular, 'sanctum')
        ->getJson('/api/users')
        ->assertForbidden();
});

it('rejects inactive users on authenticated routes', function (): void {
    $inactive = actingUser('user', false);

    $this->actingAs($inactive, 'sanctum')
        ->getJson('/api/me')
        ->assertForbidden()
        ->assertJsonPath('error.code', 403);
});

it('uses store document request authorization and validation', function (): void {
    Storage::fake('local');
    $admin = actingUser('admin');
    $reader = actingUser('user');
    $contributor = actingUser('user');

    $space = Space::create([
        'name' => 'Docs',
        'created_by' => $admin->id,
    ]);

    $space->members()->attach($reader->id, ['role' => 'reader', 'joined_at' => now()]);
    $space->members()->attach($contributor->id, ['role' => 'contributor', 'joined_at' => now()]);

    $file = UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.4\n%EOF");

    $this->actingAs($reader, 'sanctum')
        ->post('/api/documents', [
            'title' => 'Doc interdit',
            'file' => $file,
            'space_id' => $space->id,
        ])
        ->assertForbidden();

    $okFile = UploadedFile::fake()->createWithContent('doc-ok.pdf', "%PDF-1.4\n%EOF");
    $this->actingAs($contributor, 'sanctum')
        ->post('/api/documents', [
            'title' => 'Doc autorisé',
            'file' => $okFile,
            'space_id' => $space->id,
        ])
        ->assertCreated();
});

it('seeds coherent demo data with users spaces and documents', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'admin@example.com')->exists())->toBeTrue();
    expect(User::query()->where('email', 'responsable@example.com')->exists())->toBeTrue();
    expect(User::query()->where('email', 'user@example.com')->exists())->toBeTrue();
    expect(Space::query()->count())->toBeGreaterThanOrEqual(2);
    expect(Document::query()->count())->toBeGreaterThanOrEqual(2);
    expect(Schema::hasTable('space_members'))->toBeTrue();
});
