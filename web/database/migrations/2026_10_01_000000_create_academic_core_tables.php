<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number', 40)->unique();
            $table->string('department', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_number', 40)->unique();
            $table->string('grade_level', 40);
            $table->string('program', 120)->nullable();
            $table->string('guardian_email', 191)->nullable();
            $table->timestamps();

            $table->index(['grade_level', 'program']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('school_year', 20);
            $table->string('term', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code', 'school_year', 'term']);
            $table->index(['teacher_id', 'is_active']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
            $table->dateTime('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id']);
            $table->index(['subject_id', 'status']);
        });

        Schema::create('student_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('attendance_rate', 5, 2);
            $table->decimal('quiz_average', 5, 2);
            $table->decimal('assignment_average', 5, 2);
            $table->unsignedInteger('late_submissions')->default(0);
            $table->unsignedInteger('missing_submissions')->default(0);
            $table->decimal('activity_score', 5, 2);
            $table->decimal('performance_trend', 6, 2)->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'snapshot_date']);
            $table->index(['subject_id', 'snapshot_date']);
            $table->index(['student_id', 'snapshot_date']);
        });

        Schema::create('student_support_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_id')->unique()->constrained('student_performance')->cascadeOnDelete();
            $table->enum('support_level', ['LOW', 'MODERATE', 'HIGH']);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('weak_topics');
            $table->text('ai_summary')->nullable();
            $table->string('model_version', 80)->nullable();
            $table->enum('analysis_source', ['ml_ai', 'ml_only', 'rules_fallback'])->default('ml_ai');
            $table->dateTime('analyzed_at')->useCurrent();
            $table->timestamps();

            $table->index(['support_level', 'analyzed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_support_analysis');
        Schema::dropIfExists('student_performance');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
    }
};
