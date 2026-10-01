<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    protected $fillable = [
        'support_analysis_id',
        'recommendation_text',
        'priority',
        'status',
        'created_by',
    ];

    public function supportAnalysis(): BelongsTo
    {
        return $this->belongsTo(StudentSupportAnalysis::class, 'support_analysis_id');
    }
}
