<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Services\TeacherStudentService;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentPerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            && app(TeacherStudentService::class)->canRecord(
                $this->user(),
                $student,
                (int) $this->input('subject_id'),
            );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'snapshot_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance_rate' => ['required', 'numeric', 'between:0,100'],
            'quiz_average' => ['required', 'numeric', 'between:0,100'],
            'assignment_average' => ['required', 'numeric', 'between:0,100'],
            'late_submissions' => ['required', 'integer', 'between:0,100'],
            'missing_submissions' => ['required', 'integer', 'between:0,100'],
            'activity_score' => ['required', 'numeric', 'between:0,100'],
            'performance_trend' => ['required', 'numeric', 'between:-100,100'],
            'weak_topics' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return list<string> */
    public function weakTopics(): array
    {
        return collect(explode(',', (string) $this->input('weak_topics')))
            ->map(fn (string $topic): string => trim($topic))
            ->filter()
            ->unique(fn (string $topic): string => mb_strtolower($topic))
            ->take(10)
            ->values()
            ->all();
    }
}
