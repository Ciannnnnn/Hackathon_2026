<?php

namespace App\Services;

use App\Exceptions\RagServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RagExtractionService
{
    public function __construct(private readonly LocalPdfExtractionService $local) {}

    /** @return array{page_count: int, character_count: int, chunks: list<array{chunk_index: int, page_number: int, content: string, token_count: int}>} */
    public function extract(string $filePath, string $originalFilename): array
    {
        $driver = (string) config('services.rag.driver', 'local');

        if ($driver === 'local') {
            return $this->local->extract($filePath);
        }

        if ($driver === 'auto') {
            try {
                return $this->extractRemote($filePath, $originalFilename);
            } catch (RagServiceException $exception) {
                Log::warning('Remote PDF extraction failed; using the Laravel-native parser.', [
                    'exception' => $exception::class,
                ]);

                return $this->local->extract($filePath);
            }
        }

        if ($driver !== 'service') {
            throw new RagServiceException('The configured PDF extraction driver is invalid.');
        }

        return $this->extractRemote($filePath, $originalFilename);
    }

    /** @return array{page_count: int, character_count: int, chunks: list<array{chunk_index: int, page_number: int, content: string, token_count: int}>} */
    private function extractRemote(string $filePath, string $originalFilename): array
    {
        if (! config('services.rag.enabled') || blank(config('services.rag.url'))) {
            throw new RagServiceException('The PDF extraction service is not enabled.');
        }

        $stream = Storage::disk('local')->readStream($filePath);
        if (! is_resource($stream)) {
            throw new RagServiceException('The stored PDF could not be read.');
        }

        try {
            $timeout = max(3, (int) config('services.rag.timeout', 20));
            $response = Http::acceptJson()
                ->connectTimeout(min($timeout, 5))
                ->timeout($timeout)
                ->attach('file', $stream, $originalFilename, ['Content-Type' => 'application/pdf'])
                ->post(rtrim((string) config('services.rag.url'), '/').'/extract');
        } catch (ConnectionException $exception) {
            throw new RagServiceException(
                'The PDF extraction service is unavailable. Start it and retry processing.',
                0,
                $exception,
            );
        } catch (Throwable $exception) {
            report($exception);
            throw new RagServiceException('The PDF could not be sent for extraction.', 0, $exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $response->successful()) {
            $message = $response->json('message');
            throw new RagServiceException(is_string($message) && $message !== ''
                ? mb_substr($message, 0, 500)
                : 'The extraction service rejected the PDF.');
        }

        return $this->validate($response->json());
    }

    /** @return array{page_count: int, character_count: int, chunks: list<array{chunk_index: int, page_number: int, content: string, token_count: int}>} */
    private function validate(mixed $payload): array
    {
        if (! is_array($payload) || ! is_numeric($payload['page_count'] ?? null) || ! is_array($payload['chunks'] ?? null)) {
            throw new RagServiceException('The extraction service returned an invalid response.');
        }

        $pageCount = (int) $payload['page_count'];
        if ($pageCount < 1 || $pageCount > 5_000 || $payload['chunks'] === [] || count($payload['chunks']) > 10_000) {
            throw new RagServiceException('The extraction service returned invalid document dimensions.');
        }

        $chunks = [];
        foreach (array_values($payload['chunks']) as $expectedIndex => $chunk) {
            $content = is_array($chunk) ? trim((string) ($chunk['content'] ?? '')) : '';
            $pageNumber = is_array($chunk) ? (int) ($chunk['page_number'] ?? 0) : 0;
            $chunkIndex = is_array($chunk) ? (int) ($chunk['chunk_index'] ?? -1) : -1;
            $tokenCount = is_array($chunk) ? (int) ($chunk['token_count'] ?? 0) : 0;

            if ($chunkIndex !== $expectedIndex || $pageNumber < 1 || $pageNumber > $pageCount || $content === '' || mb_strlen($content) > 20_000 || $tokenCount < 1) {
                throw new RagServiceException('The extraction service returned malformed text chunks.');
            }

            $chunks[] = compact('chunkIndex', 'pageNumber', 'content', 'tokenCount');
        }

        return [
            'page_count' => $pageCount,
            'character_count' => array_sum(array_map(fn (array $chunk): int => mb_strlen($chunk['content']), $chunks)),
            'chunks' => array_map(fn (array $chunk): array => [
                'chunk_index' => $chunk['chunkIndex'],
                'page_number' => $chunk['pageNumber'],
                'content' => $chunk['content'],
                'token_count' => $chunk['tokenCount'],
            ], $chunks),
        ];
    }
}
