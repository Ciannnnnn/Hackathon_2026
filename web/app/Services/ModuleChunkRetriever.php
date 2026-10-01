<?php

namespace App\Services;

use App\Models\ModuleChunk;
use App\Models\Subject;
use Illuminate\Support\Collection;

class ModuleChunkRetriever
{
    /**
     * @return list<array{content: string, source: array{module_id: int, module_title: string, chunk_id: int, page: int, excerpt: string}, score: int}>
     */
    public function retrieve(Subject $subject, string $question, int $limit = 4): array
    {
        $terms = $this->terms($question);
        if ($terms === []) {
            return [];
        }

        $candidates = ModuleChunk::query()
            ->select([
                'module_chunks.id',
                'module_chunks.module_id',
                'module_chunks.page_number',
                'module_chunks.content',
                'modules.title as module_title',
            ])
            ->join('modules', 'modules.id', '=', 'module_chunks.module_id')
            ->where('modules.subject_id', $subject->id)
            ->where('modules.processing_status', 'ready')
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->orWhere('module_chunks.content', 'like', "%{$term}%")
                        ->orWhere('modules.title', 'like', "%{$term}%");
                }
            })
            ->limit(200)
            ->get();

        $phrase = mb_strtolower(trim($question));

        return $candidates
            ->map(function ($chunk) use ($terms, $phrase): array {
                $content = mb_strtolower($chunk->content);
                $title = mb_strtolower($chunk->module_title);
                $score = collect($terms)->sum(
                    fn (string $term): int => min(5, substr_count($content, $term))
                        + (str_contains($title, $term) ? 3 : 0),
                );

                if (mb_strlen($phrase) >= 8 && str_contains($content, $phrase)) {
                    $score += 8;
                }

                return [
                    'content' => mb_substr(trim($chunk->content), 0, 4_000),
                    'source' => [
                        'module_id' => (int) $chunk->module_id,
                        'module_title' => (string) $chunk->module_title,
                        'chunk_id' => (int) $chunk->id,
                        'page' => (int) $chunk->page_number,
                        'excerpt' => mb_substr(trim($chunk->content), 0, 240),
                    ],
                    'score' => (int) $score,
                ];
            })
            ->filter(fn (array $result): bool => $result['score'] > 0)
            ->sortByDesc('score')
            ->take(max(1, min(8, $limit)))
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function terms(string $question): array
    {
        $stopWords = [
            'about', 'after', 'also', 'and', 'are', 'can', 'could', 'does', 'explain', 'for', 'from',
            'how', 'into', 'its', 'please', 'that', 'the', 'their', 'then', 'this', 'was', 'what',
            'when', 'where', 'which', 'why', 'will', 'with', 'would', 'you', 'your',
        ];

        return Collection::make(preg_split('/[^\pL\pN]+/u', mb_strtolower($question)) ?: [])
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3 && ! in_array($term, $stopWords, true))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }
}
