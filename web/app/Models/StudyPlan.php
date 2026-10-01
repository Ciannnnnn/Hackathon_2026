<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyPlan extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'support_analysis_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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

    public function supportAnalysis(): BelongsTo
    {
        return $this->belongsTo(StudentSupportAnalysis::class, 'support_analysis_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudyPlanItem::class)->orderBy('day_number');
    }
}
