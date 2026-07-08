<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('space_id')
                  ->nullable()
                  ->constrained('spaces')
                  ->onDelete('cascade');
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('folders')
                  ->onDelete('cascade');
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajout de la FK folder_id sur documents maintenant que folders existe
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('folder_id')
                  ->references('id')
                  ->on('folders')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
        });

        Schema::dropIfExists('folders');
    }
};