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
    Schema::create('spaces', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->enum('type', ['public', 'private'])->default('public');
        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->timestamps();
    });

    Schema::create('space_members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('space_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->enum('role', ['admin', 'contributeur', 'lecteur'])->default('lecteur');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('space_members');
    Schema::dropIfExists('spaces');
}
};



