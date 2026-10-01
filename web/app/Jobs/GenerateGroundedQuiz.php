<?php

namespace App\Jobs;

use App\Models\LearningModule;
use App\Models\User;
use App\Services\QuizGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateGroundedQuiz implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    /** @param array<string, mixed> $data */
    public function __construct(
        public readonly User $creator,
        public readonly LearningModule $module,
        public readonly array $data,
    ) {
        $this->onQueue('ai');
    }

    public function handle(QuizGenerationService $generator): void
    {
        $generator->generate($this->creator, $this->module, $this->data);
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [5];
    }
}
