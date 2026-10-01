<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_analysis_id')->constrained('student_support_analysis')->cascadeOnDelete();
            $table->string('recommendation_text', 500);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'dismissed'])->default('pending');
            $table->enum('created_by', ['ai', 'teacher'])->default('ai');
            $table->timestamps();

            $table->index(['support_analysis_id', 'status']);
        });

        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('support_analysis_id')->nullable()->constrained('student_support_analysis')->nullOnDelete();
            $table->string('title', 180);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->enum('generated_by', ['ai', 'teacher'])->default('ai');
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });

        Schema::create('study_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->date('scheduled_date');
            $table->string('topic', 160);
            $table->text('task');
            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['study_plan_id', 'day_number']);
            $table->index(['scheduled_date', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan_items');
        Schema::dropIfExists('study_plans');
        Schema::dropIfExists('recommendations');
    }
};
