<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentPerformance extends Model
{
    protected $table = 'student_performance';

    protected $fillable = [
        'student_id',
        'subject_id',
        'snapshot_date',
        'attendance_rate',
        'quiz_average',
        'assignment_average',
        'late_submissions',
        'missing_submissions',
        'activity_score',
        'performance_trend',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'attendance_rate' => 'decimal:2',
            'quiz_average' => 'decimal:2',
            'assignment_average' => 'decimal:2',
            'activity_score' => 'decimal:2',
            'performance_trend' => 'decimal:2',
            'late_submissions' => 'integer',
            'missing_submissions' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function supportAnalysis(): HasOne
    {
        return $this->hasOne(StudentSupportAnalysis::class, 'performance_id');
    }
}
