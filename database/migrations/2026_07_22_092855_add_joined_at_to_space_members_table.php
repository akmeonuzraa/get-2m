<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('space_members') || Schema::hasColumn('space_members', 'joined_at')) {
            return;
        }

        Schema::table('space_members', function (Blueprint $table): void {
            $table->timestamp('joined_at')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('space_members') || !Schema::hasColumn('space_members', 'joined_at')) {
            return;
        }

        Schema::table('space_members', function (Blueprint $table): void {
            $table->dropColumn('joined_at');
        });
    }
};
