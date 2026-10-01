<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Smalot\PdfParser\Parser;
use Throwable;

class SystemHealthService
{
    /** @var list<string> */
    private const REQUIRED_TABLES = [
        'users',
        'teachers',
        'students',
        'subjects',
        'enrollments',
        'attendance',
        'assignments',
        'submissions',
        'quiz_results',
        'modules',
        'module_chunks',
        'student_performance',
        'student_support_analysis',
        'recommendations',
        'study_plans',
        'study_plan_items',
        'chat_conversations',
        'chat_messages',
        'generated_quizzes',
        'generated_quiz_questions',
        'quiz_attempts',
        'quiz_attempt_answers',
    ];

    /** @return array<string, mixed> */
    public function check(): array
    {
        $database = $this->checkDatabase();

        return [
            'status' => $database['status'] === 'ready' ? 'ok' : 'degraded',
            'application' => [
                'name' => config('app.name'),
                'environment' => app()->environment(),
                'laravel_version' => app()->version(),
            ],
            'database' => $database,
            'integrations' => [
                'gemini' => [
                    'configured' => filled(config('services.gemini.key')),
                    'provider' => config('services.ai.provider'),
                    'model' => config('services.gemini.model'),
                    'demo_fallback' => (bool) config('services.gemini.demo_fallback'),
                ],
                'ml_service' => [
                    'configured' => (bool) config('services.ml.enabled') && filled(config('services.ml.url')),
                ],
                'rag_service' => [
                    'configured' => config('services.rag.driver') === 'local'
                        ? class_exists(Parser::class)
                        : (bool) config('services.rag.enabled') && filled(config('services.rag.url')),
                    'driver' => config('services.rag.driver'),
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function checkDatabase(): array
    {
        $connectionName = (string) config('database.default');
        $startedAt = hrtime(true);

        try {
            DB::connection($connectionName)->select('SELECT 1');

            $missingTables = array_values(array_filter(
                self::REQUIRED_TABLES,
                fn (string $table): bool => ! Schema::connection($connectionName)->hasTable($table),
            ));

            return [
                'status' => $missingTables === [] ? 'ready' : 'schema_incomplete',
                'connection' => $connectionName,
                'database' => (string) config("database.connections.{$connectionName}.database"),
                'latency_ms' => $this->elapsedMilliseconds($startedAt),
                'missing_tables' => $missingTables,
            ];
        } catch (Throwable) {
            return [
                'status' => 'unavailable',
                'connection' => $connectionName,
                'database' => (string) config("database.connections.{$connectionName}.database"),
                'latency_ms' => $this->elapsedMilliseconds($startedAt),
                'message' => 'The database connection is unavailable. Check the configured DB_* variables.',
            ];
        }
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
