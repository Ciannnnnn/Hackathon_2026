<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreLearningModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacherId = $this->user()?->teacher()->value('id');

        return $teacherId
            && $this->user()->teacher->subjects()
                ->whereKey((int) $this->input('subject_id'))
                ->where('is_active', true)
                ->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maximumKilobytes = max(1, (int) config('services.rag.max_module_size_mb', 10)) * 1024;

        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:180'],
            'document' => ['required', File::types(['pdf', 'docx'])->max($maximumKilobytes), 'extensions:pdf,docx'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'document.extensions' => 'The learning material must use the .pdf or .docx extension.',
        ];
    }
}
