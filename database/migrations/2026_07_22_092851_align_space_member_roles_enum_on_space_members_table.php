<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('space_members') || !Schema::hasColumn('space_members', 'role')) {
            return;
        }

        DB::table('space_members')->whereIn('role', ['owner', 'super_admin'])->update(['role' => 'admin']);
        DB::table('space_members')->whereIn('role', ['editor', 'writer', 'member', 'contributeur'])->update(['role' => 'contributor']);
        DB::table('space_members')->whereIn('role', ['viewer', 'guest', 'lecteur'])->update(['role' => 'reader']);

        $allowedRoles = ['admin', 'contributor', 'reader'];
        $orphanedRoles = DB::table('space_members')
            ->where(function ($query) use ($allowedRoles): void {
                $query->whereNotIn('role', $allowedRoles)
                    ->orWhereNull('role');
            })
            ->distinct()
            ->orderBy('role')
            ->pluck('role')
            ->map(static fn ($role): string => $role ?? 'NULL')
            ->all();

        if ($orphanedRoles !== []) {
            throw new \RuntimeException('Cannot align space_members.role enum; unmapped values remain: '.implode(', ', $orphanedRoles));
        }

        $driver = DB::getDriverName();

        // Ce changement de type de colonne doit être vérifié manuellement sur un environnement MySQL réel avant la fusion finale — voir test "fails migration when unmapped role values remain" pour la garantie côté données.
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE space_members MODIFY COLUMN role ENUM('admin','contributor','reader') NOT NULL DEFAULT 'reader'");
        } elseif ($driver === 'sqlite') {
            DB::statement("
                CREATE TABLE space_members_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    space_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    role TEXT NOT NULL DEFAULT 'reader' CHECK (role IN ('admin', 'contributor', 'reader')),
                    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY(space_id) REFERENCES spaces(id) ON DELETE CASCADE,
                    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE(space_id, user_id)
                )
            ");

            DB::statement('
                INSERT INTO space_members_new (id, space_id, user_id, role, joined_at, created_at, updated_at)
                SELECT id, space_id, user_id, role, joined_at, created_at, updated_at
                FROM space_members
            ');

            DB::statement('DROP TABLE space_members');
            DB::statement('ALTER TABLE space_members_new RENAME TO space_members');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('space_members') || !Schema::hasColumn('space_members', 'role')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE space_members MODIFY COLUMN role ENUM('owner','editor','viewer') NOT NULL DEFAULT 'viewer'");
            DB::table('space_members')->where('role', 'admin')->update(['role' => 'owner']);
            DB::table('space_members')->where('role', 'contributor')->update(['role' => 'editor']);
            DB::table('space_members')->where('role', 'reader')->update(['role' => 'viewer']);
        }
    }
};
