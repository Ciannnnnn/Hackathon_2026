<?php

namespace App\Services;

use App\Models\GeneratedQuiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class QuizGradingService
{
    /** @param array<int|string, mixed> $answers */
    public function grade(Student $student, GeneratedQuiz $quiz, array $answers): QuizAttempt
    {
        $quiz->loadMissing(['questions', 'module']);
        $score = 0;
        $graded = $quiz->questions->map(function ($question) use ($answers, &$score): array {
            $answer = trim((string) ($answers[$question->id] ?? ''));
            $correct = $answer !== '' && $this->normalize($answer) === $this->normalize($question->correct_answer);
            $score += $correct ? 1 : 0;

            return [
                'question_id' => $question->id,
                'answer_text' => $answer !== '' ? $answer : null,
                'is_correct' => $correct,
                'feedback' => $correct ? 'Correct. '.$question->explanation : "Correct answer: {$question->correct_answer}. {$question->explanation}",
            ];
        });
        $maximum = $quiz->questions->count();
        $percentage = $maximum > 0 ? ($score / $maximum) * 100 : 0;

        return DB::transaction(function () use ($student, $quiz, $score, $maximum, $graded, $percentage): QuizAttempt {
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'score' => $score,
                'max_score' => $maximum,
                'strong_topics' => $percentage >= 75 ? [$quiz->topic] : [],
                'weak_topics' => $percentage < 75 ? [$quiz->topic] : [],
                'recommended_review' => $percentage >= 75
                    ? 'Review the explanations and continue to the next lesson.'
                    : "Review {$quiz->topic} in ".($quiz->module?->title ?? 'the learning module').' and retry after focused practice.',
                'started_at' => now(),
                'completed_at' => now(),
            ]);
            $attempt->answers()->createMany($graded->all());

            return $attempt;
        });
    }

    private function normalize(string $answer): string
    {
        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($answer)));
    }
}
