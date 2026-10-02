<?php

namespace App\Services;

use App\Data\AiResult;
use App\Models\ChatConversation;
use App\Models\Student;
use App\Models\Subject;
use App\Services\AI\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TutorService
{
    public function __construct(
        private readonly ModuleChunkRetriever $retriever,
        private readonly AiService $ai,
    ) {}

    /** @return array{conversation: ChatConversation, result: AiResult, sources: list<array<string, mixed>>} */
    public function ask(
        Student $student,
        Subject $subject,
        string $question,
        ?ChatConversation $conversation = null,
    ): array {
        if ($conversation && ($conversation->student_id !== $student->id || $conversation->subject_id !== $subject->id)) {
            throw ValidationException::withMessages(['conversation' => 'The selected conversation does not belong to this subject.']);
        }

        $matches = $this->retriever->retrieve($subject, $question);
        $sources = array_values(array_map(fn (array $match): array => $match['source'], $matches));
        $context = collect($matches)->map(
            fn (array $match, int $index): string => sprintf(
                "[Source %d: %s, %s]\n%s",
                $index + 1,
                $match['source']['module_title'],
                $match['source']['location'] ?? 'page '.$match['source']['page'],
                $match['content'],
            ),
        )->implode("\n\n");
        $history = $this->history($conversation);
        $result = $this->ai->answerTutorQuestion($question, $context, $history);

        if (! is_string($result->content) || trim($result->content) === '') {
            throw ValidationException::withMessages(['question' => 'The tutor returned an empty answer. Please try again.']);
        }

        $conversation = DB::transaction(function () use ($student, $subject, $question, $conversation, $result, $sources): ChatConversation {
            $conversation ??= ChatConversation::create([
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'title' => mb_substr(trim($question), 0, 180),
            ]);

            $conversation->messages()->create(['role' => 'user', 'content' => trim($question)]);
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => mb_substr(trim($result->content), 0, 20_000),
                'retrieved_context' => $sources !== [] ? $sources : null,
            ]);
            $conversation->touch();

            return $conversation;
        });

        return compact('conversation', 'result', 'sources');
    }

    private function history(?ChatConversation $conversation): string
    {
        if (! $conversation) {
            return '';
        }

        return $conversation->messages()
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(fn ($message): string => ucfirst($message->role).': '.mb_substr($message->content, 0, 1_000))
            ->implode("\n");
    }
}
