<?php

namespace App\Services\AI;

use App\Contracts\GenerativeAiProvider;
use App\Data\AiResult;
use App\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

class GeminiProvider implements GenerativeAiProvider
{
    public function generateText(string $systemInstruction, string $prompt): AiResult
    {
        return $this->request($systemInstruction, $prompt);
    }

    public function generateJson(string $systemInstruction, string $prompt, array $schema): AiResult
    {
        $result = $this->request($systemInstruction, $prompt, $schema);

        try {
            $content = json_decode((string) $result->content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiProviderException('Gemini returned malformed structured output.', previous: $exception);
        }

        if (! is_array($content)) {
            throw new AiProviderException('Gemini structured output did not contain a JSON object.');
        }

        return new AiResult($content, $result->provider, $result->model, usage: $result->usage);
    }

    /** @param array<string, mixed>|null $schema */
    private function request(string $systemInstruction, string $prompt, ?array $schema = null): AiResult
    {
        $key = trim((string) config('services.gemini.key'));
        $model = trim((string) config('services.gemini.model'));

        if ($key === '') {
            throw new AiProviderException('Gemini is not configured. Add GEMINI_API_KEY to web/.env.');
        }

        if ($model === '') {
            throw new AiProviderException('No Gemini model is configured.');
        }

        $generationConfig = [
            'temperature' => 0.2,
            'maxOutputTokens' => (int) config('services.gemini.max_output_tokens', 2048),
        ];

        if ($schema !== null) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $schema;
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => $generationConfig,
        ];
        $models = collect([$model, ...(array) config('services.gemini.fallback_models', [])])
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values();
        $response = null;
        $selectedModel = $model;

        foreach ($models as $index => $candidateModel) {
            $hasAnotherModel = $index < $models->count() - 1;

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->connectTimeout(min(5, (int) config('services.gemini.timeout', 15)))
                    ->timeout((int) config('services.gemini.timeout', 15))
                    ->post($this->endpoint($candidateModel), $payload);
            } catch (ConnectionException $exception) {
                if ($hasAnotherModel) {
                    continue;
                }

                throw new AiProviderException('Gemini is temporarily unreachable.', previous: $exception);
            } catch (Throwable $exception) {
                throw new AiProviderException('Gemini request failed unexpectedly.', previous: $exception);
            }

            $selectedModel = $candidateModel;

            if ($response->successful()) {
                break;
            }

            if (! $hasAnotherModel || ! $this->shouldTryNextModel($response)) {
                $this->ensureSuccessful($response);
            }
        }

        if (! $response instanceof Response) {
            throw new AiProviderException('No Gemini model is available.');
        }

        $this->ensureSuccessful($response);
        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter(fn ($part): bool => is_string($part))
            ->implode('');

        if (trim($text) === '') {
            $blocked = $response->json('promptFeedback.blockReason');
            throw new AiProviderException($blocked
                ? "Gemini blocked the request: {$blocked}."
                : 'Gemini returned an empty response.');
        }

        return new AiResult(
            trim($text),
            'gemini',
            (string) ($response->json('modelVersion') ?: $selectedModel),
            usage: array_filter([
                'prompt_tokens' => $response->json('usageMetadata.promptTokenCount'),
                'response_tokens' => $response->json('usageMetadata.candidatesTokenCount'),
                'total_tokens' => $response->json('usageMetadata.totalTokenCount'),
            ], fn ($value): bool => is_int($value)),
        );
    }

    private function endpoint(string $model): string
    {
        return rtrim((string) config('services.gemini.base_url'), '/')
            .'/models/'.rawurlencode($model).':generateContent';
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = match ($response->status()) {
            400 => 'Gemini rejected the request configuration.',
            401, 403 => 'Gemini rejected the API key or its permissions.',
            404 => 'The configured Gemini model is unavailable for this API key.',
            429 => 'Gemini rate limit reached. Try again shortly.',
            500, 502, 503, 504 => 'Gemini is temporarily overloaded or unavailable. Try again shortly.',
            default => 'Gemini returned an unavailable response.',
        };

        throw new AiProviderException($message);
    }

    private function shouldTryNextModel(Response $response): bool
    {
        return in_array($response->status(), [404, 429, 500, 502, 503, 504], true);
    }
}
