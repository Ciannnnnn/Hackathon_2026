<?php

namespace App\Contracts;

use App\Data\AiResult;

interface GenerativeAiProvider
{
    public function generateText(string $systemInstruction, string $prompt): AiResult;

    /** @param array<string, mixed> $schema */
    public function generateJson(string $systemInstruction, string $prompt, array $schema): AiResult;
}
