<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningModule extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'subject_id',
        'teacher_id',
        'title',
        'original_filename',
        'stored_filename',
        'file_path',
        'mime_type',
        'file_size_bytes',
        'processing_status',
        'processing_error',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ModuleChunk::class, 'module_id')->orderBy('chunk_index');
    }

    public function firstChunk(): HasOne
    {
        return $this->hasOne(ModuleChunk::class, 'module_id')->oldestOfMany('chunk_index');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(GeneratedQuiz::class, 'module_id');
    }

    public function isDocx(): bool
    {
        return $this->mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    public function sourceUnit(): string
    {
        return $this->isDocx() ? 'section' : 'page';
    }
}
