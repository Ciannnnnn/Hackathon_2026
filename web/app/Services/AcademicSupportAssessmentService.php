<?php

namespace App\Services;

use App\Models\StudentPerformance;
use App\Models\StudentSupportAnalysis;

class AcademicSupportAssessmentService
{
    /**
     * Create a provisional, explainable assessment until the ML service is introduced in Phase 6.
     *
     * @param  list<string>  $weakTopics
     */
    public function assess(StudentPerformance $performance, array $weakTopics = []): StudentSupportAnalysis
    {
        $riskPoints = 0;
        $focusAreas = [];

        $riskPoints += $this->scoreBelow((float) $performance->attendance_rate, 75, 85);
        $riskPoints += $this->scoreBelow((float) $performance->quiz_average, 65, 75);
        $riskPoints += $this->scoreBelow((float) $performance->assignment_average, 65, 75);
        $riskPoints += $this->scoreBelow((float) $performance->activity_score, 50, 70);
        $riskPoints += $performance->missing_submissions >= 2 ? 2 : ($performance->missing_submissions === 1 ? 1 : 0);
        $riskPoints += $performance->late_submissions >= 3 ? 1 : 0;
        $riskPoints += (float) $performance->performance_trend <= -8
            ? 2
            : ((float) $performance->performance_trend < -2 ? 1 : 0);

        if ((float) $performance->attendance_rate < 85) {
            $focusAreas[] = 'attendance';
        }

        if ((float) $performance->quiz_average < 75) {
            $focusAreas[] = 'quiz performance';
        }

        if ((float) $performance->assignment_average < 75 || $performance->missing_submissions > 0) {
            $focusAreas[] = 'assignment completion';
        }

        if ((float) $performance->activity_score < 70) {
            $focusAreas[] = 'course activity';
        }

        if ((float) $performance->performance_trend < -2) {
            $focusAreas[] = 'recent performance trend';
        }

        $level = match (true) {
            $riskPoints >= 7 => 'HIGH',
            $riskPoints >= 3 => 'MODERATE',
            default => 'LOW',
        };

        return StudentSupportAnalysis::updateOrCreate(
            ['performance_id' => $performance->id],
            [
                'support_level' => $level,
                'confidence' => null,
                'weak_topics' => array_values(array_unique($weakTopics)),
                'ai_summary' => $this->summary($level, $focusAreas),
                'model_version' => 'phase5-rules-v1',
                'analysis_source' => 'rules_fallback',
                'analyzed_at' => now(),
            ],
        );
    }

    private function scoreBelow(float $value, float $highRiskThreshold, float $moderateRiskThreshold): int
    {
        return match (true) {
            $value < $highRiskThreshold => 2,
            $value < $moderateRiskThreshold => 1,
            default => 0,
        };
    }

    /** @param list<string> $focusAreas */
    private function summary(string $level, array $focusAreas): string
    {
        if ($focusAreas === []) {
            return 'Current academic indicators are stable. Continue the present learning routine and monitor future assessments.';
        }

        $areas = implode(', ', array_unique($focusAreas));

        return match ($level) {
            'HIGH' => "Current records indicate a high academic support need, especially in {$areas}. Prompt, structured follow-up is recommended.",
            'MODERATE' => "Current records suggest targeted support may help with {$areas}. Review progress again after focused practice.",
            default => "Current academic indicators are generally stable, with {$areas} worth monitoring.",
        };
    }
}
