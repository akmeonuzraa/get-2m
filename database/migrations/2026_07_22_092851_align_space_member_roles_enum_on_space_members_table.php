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

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE space_members MODIFY COLUMN role ENUM('admin','contributor','reader') NOT NULL DEFAULT 'reader'");
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
