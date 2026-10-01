<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = ['quiz_id', 'student_id', 'score', 'max_score', 'strong_topics', 'weak_topics', 'recommended_review', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'max_score' => 'decimal:2', 'strong_topics' => 'array', 'weak_topics' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(GeneratedQuiz::class, 'quiz_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'attempt_id');
    }
}
