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
        Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['admin', 'responsable', 'user'])->default('user')->after('email');
        $table->string('service')->nullable()->after('role');
        $table->string('avatar')->nullable()->after('service');
        $table->boolean('is_active')->default(true)->after('avatar');
        $table->timestamp('last_login_at')->nullable()->after('is_active');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['role', 'service', 'avatar', 'is_active', 'last_login_at']);
        });
    }
};
