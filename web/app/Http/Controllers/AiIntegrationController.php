<?php

namespace App\Http\Controllers;

use App\Contracts\GenerativeAiProvider;
use App\Exceptions\AiProviderException;
use Illuminate\Http\RedirectResponse;

class AiIntegrationController extends Controller
{
    public function __invoke(GenerativeAiProvider $provider): RedirectResponse
    {
        try {
            $result = $provider->generateText(
                'You are an EduPulse AI connectivity check. Follow the requested output exactly.',
                'Reply with exactly: EduPulse AI ready',
            );
        } catch (AiProviderException $exception) {
            return back()->withErrors(['gemini' => $exception->getMessage()]);
        }

        return back()->with(
            'status',
            "Gemini connection succeeded using {$result->model}.",
        );
    }
}
