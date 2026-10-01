<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->string('original_filename');
            $table->string('stored_filename')->unique();
            $table->string('file_path', 500);
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedBigInteger('file_size_bytes');
            $table->enum('processing_status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->text('processing_error')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();
            $table->timestamps();

            $table->index(['subject_id', 'processing_status']);
        });

        Schema::create('module_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('page_number')->nullable();
            $table->mediumText('content');
            $table->unsignedInteger('token_count')->nullable();
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'chunk_index']);
            $table->index(['module_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_chunks');
        Schema::dropIfExists('modules');
    }
};
