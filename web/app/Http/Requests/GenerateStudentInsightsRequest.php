<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Services\TeacherStudentService;
use Illuminate\Foundation\Http\FormRequest;

class GenerateStudentInsightsRequest extends FormRequest
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
        ];
    }
}
