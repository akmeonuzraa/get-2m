<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                  ->constrained('documents')
                  ->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_filename');
            $table->bigInteger('file_size');
            $table->integer('version_number');
            $table->text('change_note')->nullable();
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};