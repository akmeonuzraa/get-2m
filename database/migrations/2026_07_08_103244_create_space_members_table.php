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
        Schema::create('space_members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('space_id')
              ->constrained('spaces')
              ->onDelete('cascade');
        $table->foreignId('user_id')
              ->constrained('users')
              ->onDelete('cascade');
        $table->enum('role', ['admin', 'contributor', 'reader'])->default('reader');
        $table->timestamp('joined_at')->useCurrent();
        $table->timestamps();

        // Un utilisateur ne peut être membre qu'une seule fois par espace
        $table->unique(['space_id', 'user_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_members');
    }
};
