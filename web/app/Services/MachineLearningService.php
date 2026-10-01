<?php

namespace App\Services;

use App\Models\StudentPerformance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MachineLearningService
{
    /** @return array{support_level: string, confidence: float, model_version: string}|null */
    public function predict(StudentPerformance $performance): ?array
    {
        if (! config('services.ml.enabled') || blank(config('services.ml.url'))) {
            return null;
        }

        try {
            $timeout = max(1, (int) config('services.ml.timeout', 5));
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(min($timeout, 3))
                ->timeout($timeout)
                ->post(rtrim((string) config('services.ml.url'), '/').'/predict', $this->payload($performance));

            if (! $response->successful()) {
                Log::warning('ML prediction service returned an unsuccessful response.', [
                    'performance_id' => $performance->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $result = $response->json();
            $level = is_array($result) ? ($result['support_level'] ?? null) : null;
            $confidence = is_array($result) ? ($result['confidence'] ?? null) : null;
            $version = is_array($result) ? ($result['model_version'] ?? null) : null;

            if (
                ! in_array($level, ['LOW', 'MODERATE', 'HIGH'], true)
                || ! is_numeric($confidence)
                || (float) $confidence < 0
                || (float) $confidence > 1
                || ! is_string($version)
                || trim($version) === ''
            ) {
                Log::warning('ML prediction service returned an invalid payload.', [
                    'performance_id' => $performance->id,
                ]);

                return null;
            }

            return [
                'support_level' => $level,
                'confidence' => round((float) $confidence, 4),
                'model_version' => mb_substr($version, 0, 80),
            ];
        } catch (ConnectionException $exception) {
            Log::notice('ML prediction service is unavailable; using the rules fallback.', [
                'performance_id' => $performance->id,
                'exception' => $exception::class,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return null;
    }

    /** @return array<string, float|int> */
    private function payload(StudentPerformance $performance): array
    {
        return [
            'attendance_rate' => (float) $performance->attendance_rate,
            'quiz_average' => (float) $performance->quiz_average,
            'assignment_average' => (float) $performance->assignment_average,
            'late_submissions' => (int) $performance->late_submissions,
            'missing_submissions' => (int) $performance->missing_submissions,
            'activity_score' => (float) $performance->activity_score,
            'performance_trend' => (float) $performance->performance_trend,
        ];
    }
}
