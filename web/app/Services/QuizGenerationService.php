<?php

namespace App\Services;

use App\Models\GeneratedQuiz;
use App\Models\LearningModule;
use App\Models\User;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizGenerationService
{
    public function __construct(private readonly AiService $ai) {}

    /** @param array<string, mixed> $data */
    public function generate(User $creator, LearningModule $module, array $data): GeneratedQuiz
    {
        $chunks = $module->chunks()->orderBy('chunk_index')->limit(30)->get();
        if ($chunks->isEmpty()) {
            throw ValidationException::withMessages(['module_id' => 'This module has no extracted text.']);
        }

        $contextParts = [];
        $includedChunkIds = [];
        $contextLength = 0;
        $sourceUnit = $module->sourceUnit();

        foreach ($chunks as $chunk) {
            $part = "[source_chunk_id={$chunk->id}; {$sourceUnit}={$chunk->page_number}]\n{$chunk->content}";
            if ($contextParts !== [] && $contextLength + mb_strlen($part) > 18_000) {
                break;
            }

            $contextParts[] = $part;
            $includedChunkIds[] = (int) $chunk->id;
            $contextLength += mb_strlen($part) + 2;
        }

        $context = implode("\n\n", $contextParts);
        $result = $this->ai->generateQuiz(
            $context,
            (int) $data['question_count'],
            $data['difficulty'],
            $data['question_type'],
        );

        if ($result->fallback) {
            throw ValidationException::withMessages(['quiz' => 'Gemini is unavailable, so a grounded quiz was not created. Please retry shortly.']);
        }

        $content = is_array($result->content) ? $result->content : [];
        $questions = $this->validateQuestions(
            $content['questions'] ?? null,
            $includedChunkIds,
            (int) $data['question_count'],
            $data['question_type'],
        );

        return DB::transaction(function () use ($creator, $module, $data, $questions): GeneratedQuiz {
            $quiz = GeneratedQuiz::create([
                'subject_id' => $module->subject_id,
                'module_id' => $module->id,
                'created_by_user_id' => $creator->id,
                'title' => $data['title'],
                'topic' => $data['topic'],
                'difficulty' => $data['difficulty'],
                'question_count' => count($questions),
                'is_published' => $data['is_published'],
            ]);

            $quiz->questions()->createMany($questions);

            return $quiz;
        });
    }

    /** @return list<array<string, mixed>> */
    private function validateQuestions(mixed $questions, array $chunkIds, int $expected, string $requestedType): array
    {
        if (! is_array($questions) || count($questions) !== $expected) {
            throw ValidationException::withMessages(['quiz' => 'Gemini returned an incomplete quiz. Please retry.']);
        }

        $validated = collect(array_values($questions))->map(function ($question, int $index) use ($chunkIds, $requestedType): array {
            $type = is_array($question) ? ($question['type'] ?? null) : null;
            $text = is_array($question) ? trim((string) ($question['question'] ?? '')) : '';
            $answer = is_array($question) ? trim((string) ($question['correct_answer'] ?? '')) : '';
            $explanation = is_array($question) ? trim((string) ($question['explanation'] ?? '')) : '';
            $sourceId = is_array($question) ? (int) ($question['source_chunk_id'] ?? 0) : 0;
            $choices = is_array($question) && is_array($question['choices'] ?? null)
                ? collect($question['choices'])->map(fn ($choice): string => trim((string) $choice))->filter()->unique()->values()->all()
                : [];

            if (! in_array($type, ['multiple_choice', 'true_false', 'short_answer'], true)
                || ($requestedType !== 'mixed' && $type !== $requestedType)
                || $text === '' || mb_strlen($text) > 1_000
                || $answer === '' || mb_strlen($answer) > 500
                || $explanation === '' || mb_strlen($explanation) > 2_000
                || ! in_array($sourceId, $chunkIds, true)) {
                throw ValidationException::withMessages(['quiz' => 'Gemini returned malformed or ungrounded questions. Please retry.']);
            }

            if ($type === 'true_false') {
                if (! in_array(mb_strtolower($answer), ['true', 'false'], true)) {
                    throw ValidationException::withMessages(['quiz' => 'Gemini returned an invalid True/False answer. Please retry.']);
                }

                $choices = ['True', 'False'];
            }

            if ($type === 'multiple_choice' && (count($choices) < 2 || ! collect($choices)->contains(fn (string $choice): bool => strcasecmp($choice, $answer) === 0))) {
                throw ValidationException::withMessages(['quiz' => 'Gemini returned an invalid multiple-choice question. Please retry.']);
            }

            return [
                'position' => $index + 1,
                'question_type' => $type,
                'question_text' => $text,
                'choices' => $type === 'short_answer' ? null : $choices,
                'correct_answer' => $answer,
                'explanation' => $explanation,
                'source_chunk_id' => $sourceId,
            ];
        })->all();

        if ($requestedType === 'mixed' && collect($validated)->pluck('question_type')->unique()->count() < 2) {
            throw ValidationException::withMessages(['quiz' => 'Gemini did not return the requested mix of question types. Please retry.']);
        }

        return $validated;
    }
}
