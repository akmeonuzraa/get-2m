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
