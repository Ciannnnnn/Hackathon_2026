<?php

namespace App\Providers;

use App\Contracts\GenerativeAiProvider;
use App\Services\AI\GeminiProvider;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GenerativeAiProvider::class, function (): GenerativeAiProvider {
            return match (config('services.ai.provider')) {
                'gemini' => new GeminiProvider,
                default => throw new LogicException('The configured AI provider is not supported.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
