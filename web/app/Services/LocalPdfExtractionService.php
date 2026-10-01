<?php

namespace App\Services;

use App\Exceptions\RagServiceException;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

class LocalPdfExtractionService
{
    private const CHUNK_SIZE = 1_200;

    private const CHUNK_OVERLAP = 200;

    /** @return array{page_count: int, character_count: int, chunks: list<array{chunk_index: int, page_number: int, content: string, token_count: int}>} */
    public function extract(string $filePath): array
    {
        try {
            $contents = Storage::disk('local')->get($filePath);
        } catch (Throwable $exception) {
            throw new RagServiceException('The stored PDF could not be read. Upload the file again and retry.', 0, $exception);
        }

        if (! is_string($contents) || ! str_starts_with($contents, '%PDF-')) {
            throw new RagServiceException('The uploaded file is not a valid PDF document.');
        }

        try {
            $pages = (new Parser)->parseContent($contents)->getPages();
        } catch (Throwable $exception) {
            report($exception);
            throw new RagServiceException('The PDF is damaged, password-protected, or uses an unsupported format.', 0, $exception);
        }

        $pageCount = count($pages);
        if ($pageCount < 1 || $pageCount > 5_000) {
            throw new RagServiceException('The PDF contains an invalid number of pages.');
        }

        $chunks = [];
        foreach ($pages as $pageIndex => $page) {
            try {
                $text = $this->normalize($page->getText());
            } catch (Throwable $exception) {
                report($exception);
                throw new RagServiceException('Text could not be extracted from this PDF.', 0, $exception);
            }

            foreach ($this->chunk($text) as $content) {
                if (count($chunks) >= 10_000) {
                    throw new RagServiceException('The PDF produced too many text sections to process safely.');
                }

                $chunks[] = [
                    'chunk_index' => count($chunks),
                    'page_number' => $pageIndex + 1,
                    'content' => $content,
                    'token_count' => count(preg_split('/\s+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: []),
                ];
            }
        }

        if ($chunks === []) {
            throw new RagServiceException('No selectable text was found. Upload a text-based PDF; scanned image PDFs require OCR.');
        }

        return [
            'page_count' => $pageCount,
            'character_count' => array_sum(array_map(fn (array $chunk): int => mb_strlen($chunk['content']), $chunks)),
            'chunks' => $chunks,
        ];
    }

    private function normalize(string $text): string
    {
        $text = str_replace("\0", ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/(?:\r?\n){3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** @return list<string> */
    private function chunk(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + self::CHUNK_SIZE, $length);
            if ($end < $length) {
                $window = mb_substr($text, $start, self::CHUNK_SIZE);
                $newline = mb_strrpos($window, "\n");
                $sentence = mb_strrpos($window, '. ');
                $boundary = max($newline === false ? -1 : $newline, $sentence === false ? -1 : $sentence + 1);

                if ($boundary > intdiv(self::CHUNK_SIZE, 2)) {
                    $end = $start + $boundary;
                }
            }

            $content = trim(mb_substr($text, $start, $end - $start));
            if ($content !== '') {
                $chunks[] = $content;
            }

            if ($end >= $length) {
                break;
            }

            $start = max($start + 1, $end - self::CHUNK_OVERLAP);
        }

        return $chunks;
    }
}
