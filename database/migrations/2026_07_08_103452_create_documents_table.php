<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Infos fichier
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('file_type');
            $table->string('mime_type');
            $table->bigInteger('file_size');

            // Métadonnées
            $table->json('keywords')->nullable();
            $table->string('service')->nullable();

            // Relations
            $table->unsignedBigInteger('folder_id')->nullable(); // FK ajoutée dans folders migration
            $table->foreignId('space_id')
                  ->nullable()
                  ->constrained('spaces')
                  ->onDelete('cascade');
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Versionnage
            $table->integer('current_version')->default(1);

            // Statut
            $table->enum('status', ['active', 'trashed'])->default('active');
            $table->timestamp('trashed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};