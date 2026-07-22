<?php

use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('maps legacy contributeur role to contributor when migration is executed', function (): void {
    $creator = User::factory()->create([
        'role' => 'admin',
    ]);

    $member = User::factory()->create([
        'role' => 'user',
    ]);

    $space = Space::create([
        'name' => 'Legacy Space',
        'created_by' => $creator->id,
    ]);

    DB::statement('PRAGMA ignore_check_constraints = 1');

    DB::table('space_members')->insert([
        'space_id' => $space->id,
        'user_id' => $member->id,
        'role' => 'contributeur',
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::statement('PRAGMA ignore_check_constraints = 0');

    /** @var \Illuminate\Database\Migrations\Migration $migration */
    $migration = require database_path('migrations/2026_07_22_092851_align_space_member_roles_enum_on_space_members_table.php');
    $migration->up();

    $role = DB::table('space_members')
        ->where('space_id', $space->id)
        ->where('user_id', $member->id)
        ->value('role');

    expect($role)->toBe('contributor');
});

it('rolls back roles to owner/editor/viewer on SQLite roundtrip', function (): void {
    $creator = User::factory()->create(['role' => 'admin']);
    $members = User::factory()->count(3)->create(['role' => 'user']);

    $space = Space::create([
        'name' => 'Roundtrip Space',
        'created_by' => $creator->id,
    ]);

    // Insert old-style role values, bypassing the current CHECK constraint
    DB::statement('PRAGMA ignore_check_constraints = 1');
    DB::table('space_members')->insert([
        ['space_id' => $space->id, 'user_id' => $members[0]->id, 'role' => 'owner', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ['space_id' => $space->id, 'user_id' => $members[1]->id, 'role' => 'editor', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ['space_id' => $space->id, 'user_id' => $members[2]->id, 'role' => 'viewer', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::statement('PRAGMA ignore_check_constraints = 0');

    /** @var \Illuminate\Database\Migrations\Migration $migration */
    $migration = require database_path('migrations/2026_07_22_092851_align_space_member_roles_enum_on_space_members_table.php');

    $migration->up();
    $migration->down();

    $roles = DB::table('space_members')
        ->where('space_id', $space->id)
        ->pluck('role', 'user_id');

    expect($roles[$members[0]->id])->toBe('owner')
        ->and($roles[$members[1]->id])->toBe('editor')
        ->and($roles[$members[2]->id])->toBe('viewer');

    $tableInfo = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='space_members'");
    expect($tableInfo)->not->toBeEmpty();
    $schemaSql = $tableInfo[0]->sql;
    expect($schemaSql)->toContain("'owner'")
        ->and($schemaSql)->toContain("'editor'")
        ->and($schemaSql)->toContain("'viewer'");
});

it('fails migration when unmapped role values remain', function (): void {
    $creator = User::factory()->create([
        'role' => 'admin',
    ]);

    $member = User::factory()->create([
        'role' => 'user',
    ]);

    $space = Space::create([
        'name' => 'Invalid Role Space',
        'created_by' => $creator->id,
    ]);

    DB::statement('PRAGMA ignore_check_constraints = 1');

    DB::table('space_members')->insert([
        'space_id' => $space->id,
        'user_id' => $member->id,
        'role' => 'inconnu',
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::statement('PRAGMA ignore_check_constraints = 0');

    /** @var \Illuminate\Database\Migrations\Migration $migration */
    $migration = require database_path('migrations/2026_07_22_092851_align_space_member_roles_enum_on_space_members_table.php');

    expect(fn () => $migration->up())
        ->toThrow(\RuntimeException::class, 'unmapped values remain: inconnu');
});
