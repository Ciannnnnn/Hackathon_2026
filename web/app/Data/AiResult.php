<?php

namespace App\Data;

class AiResult
{
    /**
     * @param  string|array<string, mixed>  $content
     * @param  array<string, int>  $usage
     */
    public function __construct(
        public readonly string|array $content,
        public readonly string $provider,
        public readonly string $model,
        public readonly bool $fallback = false,
        public readonly array $usage = [],
    ) {}
}
