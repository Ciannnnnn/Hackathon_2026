<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->enum('status', ['present', 'late', 'absent', 'excused']);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'session_date']);
            $table->index(['session_date', 'status']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('topic', 160);
            $table->text('instructions')->nullable();
            $table->decimal('max_score', 7, 2)->default(100);
            $table->dateTime('due_at');
            $table->timestamps();

            $table->index(['subject_id', 'due_at']);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->enum('status', ['submitted', 'late', 'missing', 'graded']);
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('topic', 160);
            $table->decimal('score', 7, 2);
            $table->decimal('max_score', 7, 2);
            $table->enum('source', ['teacher', 'ai_generated'])->default('teacher');
            $table->dateTime('taken_at');
            $table->timestamps();

            $table->index(['student_id', 'taken_at']);
            $table->index(['subject_id', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('attendance');
    }
};
