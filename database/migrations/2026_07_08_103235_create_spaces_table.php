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
        $table->string('cover_image')->nullable();  // image de couverture optionnelle
        $table->foreignId('created_by')
              ->constrained('users')
              ->onDelete('cascade');
        $table->timestamps();
        $table->softDeletes(); // permet de supprimer sans perdre les données
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
