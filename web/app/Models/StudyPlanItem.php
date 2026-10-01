<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanItem extends Model
{
    protected $fillable = [
        'study_plan_id',
        'day_number',
        'scheduled_date',
        'topic',
        'task',
        'is_completed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'scheduled_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }
}
