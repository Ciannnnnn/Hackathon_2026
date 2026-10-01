<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 180);
            $table->string('topic', 160);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedTinyInteger('question_count');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['subject_id', 'is_published']);
        });

        Schema::create('generated_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('generated_quizzes')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer']);
            $table->text('question_text');
            $table->json('choices')->nullable();
            $table->text('correct_answer');
            $table->text('explanation');
            $table->foreignId('source_chunk_id')->nullable()->constrained('module_chunks')->nullOnDelete();
            $table->timestamps();
            $table->unique(['quiz_id', 'position']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('generated_quizzes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('max_score', 7, 2)->nullable();
            $table->json('strong_topics')->nullable();
            $table->json('weak_topics')->nullable();
            $table->text('recommended_review')->nullable();
            $table->dateTime('started_at')->useCurrent();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'completed_at']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('generated_quiz_questions')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('generated_quiz_questions');
        Schema::dropIfExists('generated_quizzes');
    }
};
