<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedQuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'position', 'question_type', 'question_text', 'choices', 'correct_answer', 'explanation', 'source_chunk_id'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'choices' => 'array'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(GeneratedQuiz::class, 'quiz_id');
    }

    public function sourceChunk(): BelongsTo
    {
        return $this->belongsTo(ModuleChunk::class, 'source_chunk_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class, 'question_id');
    }
}
