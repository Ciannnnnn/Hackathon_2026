<?php

namespace App\Services\AI;

use App\Contracts\GenerativeAiProvider;
use App\Data\AiResult;
use App\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function __construct(private readonly GenerativeAiProvider $provider) {}

    /** @param array<string, mixed> $studentData */
    public function analyzeStudentPerformance(array $studentData): AiResult
    {
        return $this->structured(
            'student-performance-analysis',
            'Analyze only the supplied academic data. Describe an academic support need, never a medical, psychological, behavioral, or mental-health diagnosis. Be concise, specific, and constructive.',
            'Analyze this student performance record and recommend practical educational support: '.json_encode($studentData, JSON_THROW_ON_ERROR),
            [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'weak_topics' => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 8],
                    'recommended_actions' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1, 'maxItems' => 8],
                ],
                'required' => ['summary', 'weak_topics', 'recommended_actions'],
            ],
            fn (): array => $this->analysisFallback($studentData),
            ['summary', 'weak_topics', 'recommended_actions'],
        );
    }

    /** @param array<string, mixed> $studentData */
    public function generateStudyPlan(array $studentData): AiResult
    {
        return $this->structured(
            'study-plan',
            'Create a realistic seven-day academic study plan using only the supplied needs. Keep every task short, actionable, and educational.',
            'Create a seven-day plan for this student: '.json_encode($studentData, JSON_THROW_ON_ERROR),
            [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'items' => [
                        'type' => 'array',
                        'minItems' => 7,
                        'maxItems' => 7,
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'day' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 7],
                                'topic' => ['type' => 'string'],
                                'task' => ['type' => 'string'],
                            ],
                            'required' => ['day', 'topic', 'task'],
                        ],
                    ],
                ],
                'required' => ['title', 'items'],
            ],
            fn (): array => $this->studyPlanFallback($studentData),
            ['title', 'items'],
        );
    }

    public function generateQuiz(string $content, int $numberOfQuestions = 5, string $difficulty = 'medium'): AiResult
    {
        $count = max(1, min(20, $numberOfQuestions));
        $difficulty = in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'medium';

        return $this->structured(
            'quiz-generation',
            'Generate an educational quiz strictly from the supplied learning content. Answers and explanations must be supported by that content.',
            "Create {$count} {$difficulty} questions from this content:\n".mb_substr($content, 0, 20_000),
            [
                'type' => 'object',
                'properties' => [
                    'questions' => [
                        'type' => 'array',
                        'minItems' => $count,
                        'maxItems' => $count,
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'question' => ['type' => 'string'],
                                'type' => ['type' => 'string', 'enum' => ['multiple_choice', 'true_false', 'short_answer']],
                                'choices' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'correct_answer' => ['type' => 'string'],
                                'explanation' => ['type' => 'string'],
                            ],
                            'required' => ['question', 'type', 'choices', 'correct_answer', 'explanation'],
                        ],
                    ],
                ],
                'required' => ['questions'],
            ],
            fn (): array => ['questions' => []],
            ['questions'],
        );
    }

    public function explainWeakTopic(string $topic, string $context = ''): AiResult
    {
        return $this->text(
            'topic-explanation',
            'Explain academic concepts simply with one concrete example. Do not claim to use teacher material unless context is supplied.',
            "Topic: {$topic}\nRelevant context: ".($context !== '' ? mb_substr($context, 0, 20_000) : 'No teacher material was retrieved.'),
            "Review {$topic} by defining the key idea, studying one worked example, and checking your understanding with a short practice question.",
        );
    }

    public function answerTutorQuestion(string $question, string $relevantContext = ''): AiResult
    {
        $groundingInstruction = $relevantContext !== ''
            ? 'Use the supplied teacher-material context and explicitly distinguish it from general explanation.'
            : 'No source context was retrieved. Answer as a general explanation and never claim that the answer comes from teacher-uploaded material.';

        return $this->text(
            'tutor-answer',
            "You are a concise, encouraging academic tutor. {$groundingInstruction}",
            "Question: {$question}\nContext: ".($relevantContext !== '' ? mb_substr($relevantContext, 0, 20_000) : 'None'),
            'The AI tutor is temporarily unavailable. Review the relevant lesson notes and ask your instructor for clarification while the service reconnects.',
        );
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  callable(): array<string, mixed>  $fallback
     * @param  list<string>  $requiredKeys
     */
    private function structured(
        string $operation,
        string $system,
        string $prompt,
        array $schema,
        callable $fallback,
        array $requiredKeys,
    ): AiResult {
        try {
            $result = $this->provider->generateJson($system, $prompt, $schema);
            $content = $result->content;

            if (! is_array($content) || collect($requiredKeys)->contains(fn (string $key): bool => ! array_key_exists($key, $content))) {
                throw new AiProviderException('The AI response did not contain all required fields.');
            }

            return $result;
        } catch (AiProviderException $exception) {
            return $this->fallback($operation, $exception, $fallback());
        }
    }

    private function text(string $operation, string $system, string $prompt, string $fallback): AiResult
    {
        try {
            return $this->provider->generateText($system, $prompt);
        } catch (AiProviderException $exception) {
            return $this->fallback($operation, $exception, $fallback);
        }
    }

    /** @param string|array<string, mixed> $content */
    private function fallback(string $operation, AiProviderException $exception, string|array $content): AiResult
    {
        if (! config('services.gemini.demo_fallback')) {
            throw $exception;
        }

        Log::notice('Generative AI fallback used.', [
            'operation' => $operation,
            'exception' => $exception::class,
        ]);

        return new AiResult($content, 'demo_fallback', 'local-v1', true);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function analysisFallback(array $data): array
    {
        $level = strtoupper((string) ($data['support_level'] ?? 'MODERATE'));

        return [
            'summary' => "The current academic indicators show a {$level} learning support level. Focused review and regular progress checks are recommended.",
            'weak_topics' => array_values((array) ($data['weak_topics'] ?? [])),
            'recommended_actions' => [
                'Review the lowest-scoring topic using class materials.',
                'Complete a short practice activity and review mistakes.',
                'Check progress with the instructor after the next assessment.',
            ],
        ];
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function studyPlanFallback(array $data): array
    {
        $topic = (string) (collect($data['weak_topics'] ?? [])->first() ?: 'priority topic');

        return [
            'title' => '7-Day Focused Review Plan',
            'items' => collect(range(1, 7))->map(fn (int $day): array => [
                'day' => $day,
                'topic' => $topic,
                'task' => match ($day) {
                    1 => "Review the core ideas for {$topic}.",
                    2 => 'Study one worked example and summarize each step.',
                    3 => 'Complete five focused practice questions.',
                    4 => 'Review mistakes and write corrected solutions.',
                    5 => 'Take a short self-check without notes.',
                    6 => 'Revisit the lowest-scoring part of the self-check.',
                    default => 'Complete a final review and record what improved.',
                },
            ])->all(),
        ];
    }
}
