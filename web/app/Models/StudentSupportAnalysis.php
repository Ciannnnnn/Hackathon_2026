<?php

namespace App\Models;

use App\Enums\SupportLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSupportAnalysis extends Model
{
    protected $table = 'student_support_analysis';

    protected $fillable = [
        'performance_id',
        'support_level',
        'confidence',
        'weak_topics',
        'ai_summary',
        'model_version',
        'analysis_source',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'support_level' => SupportLevel::class,
            'confidence' => 'decimal:4',
            'weak_topics' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(StudentPerformance::class, 'performance_id');
    }
}
