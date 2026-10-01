<?php

namespace Tests\Feature;

use App\Data\AiResult;
use App\Exceptions\AiProviderException;
use App\Models\User;
use App\Services\AI\AiService;
use App\Services\AI\GeminiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.model' => 'gemini-test-model',
            'services.gemini.fallback_models' => [],
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.timeout' => 5,
            'services.gemini.demo_fallback' => true,
        ]);
    }

    public function test_gemini_provider_sends_key_in_header_and_parses_structured_output(): void
    {
        Http::fake([
            'https://gemini.test/v1beta/models/gemini-test-model:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'summary' => 'Focused support is recommended.',
                            'weak_topics' => ['Database Normalization'],
                            'recommended_actions' => ['Review 2NF and 3NF.'],
                        ]),
                    ]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 100,
                    'candidatesTokenCount' => 40,
                    'totalTokenCount' => 140,
                ],
                'modelVersion' => 'gemini-test-model-001',
            ]),
        ]);

        $result = (new GeminiProvider)->generateJson('System instruction', 'Analyze student', [
            'type' => 'object',
            'properties' => ['summary' => ['type' => 'string']],
            'required' => ['summary'],
        ]);

        $this->assertInstanceOf(AiResult::class, $result);
        $this->assertSame('Focused support is recommended.', $result->content['summary']);
        $this->assertSame('gemini', $result->provider);
        $this->assertSame('gemini-test-model-001', $result->model);
        $this->assertSame(140, $result->usage['total_tokens']);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://gemini.test/v1beta/models/gemini-test-model:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && $request['contents'][0]['parts'][0]['text'] === 'Analyze student';
        });
    }

    public function test_invalid_or_unavailable_gemini_responses_use_safe_fallback_content(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Unavailable']], 503)]);

        $result = app(AiService::class)->analyzeStudentPerformance([
            'support_level' => 'HIGH',
            'weak_topics' => ['Database Normalization'],
        ]);

        $this->assertTrue($result->fallback);
        $this->assertSame('demo_fallback', $result->provider);
        $this->assertSame(['Database Normalization'], $result->content['weak_topics']);
        $this->assertCount(3, $result->content['recommended_actions']);
    }

    public function test_provider_uses_a_fallback_model_when_primary_is_overloaded(): void
    {
        config(['services.gemini.fallback_models' => ['gemini-fallback-model']]);
        Http::fake([
            'https://gemini.test/v1beta/models/gemini-test-model:generateContent' => Http::response([], 503),
            'https://gemini.test/v1beta/models/gemini-fallback-model:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'EduPulse AI ready']]],
                    'finishReason' => 'STOP',
                ]],
                'modelVersion' => 'gemini-fallback-model-001',
            ]),
        ]);

        $result = (new GeminiProvider)->generateText('System instruction', 'Connectivity check');

        $this->assertSame('EduPulse AI ready', $result->content);
        $this->assertSame('gemini-fallback-model-001', $result->model);
        Http::assertSentCount(2);
    }

    public function test_provider_uses_a_fallback_model_when_primary_connection_times_out(): void
    {
        config(['services.gemini.fallback_models' => ['gemini-fallback-model']]);
        Http::fake([
            'https://gemini.test/v1beta/models/gemini-test-model:generateContent' => Http::failedConnection('Timed out'),
            'https://gemini.test/v1beta/models/gemini-fallback-model:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Fallback connection ready']]],
                    'finishReason' => 'STOP',
                ]],
                'modelVersion' => 'gemini-fallback-model-connection-001',
            ]),
        ]);

        $result = (new GeminiProvider)->generateText('System instruction', 'Connectivity check');

        $this->assertSame('Fallback connection ready', $result->content);
        $this->assertSame('gemini-fallback-model-connection-001', $result->model);
        Http::assertSentCount(2);
    }

    public function test_ai_errors_are_propagated_when_demo_fallback_is_disabled(): void
    {
        config(['services.gemini.demo_fallback' => false]);
        Http::fake(['*' => Http::response([], 429)]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('rate limit');

        app(AiService::class)->explainWeakTopic('Database Normalization');
    }

    public function test_administrator_can_test_gemini_without_exposing_the_key(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'EduPulse AI ready']]],
                'finishReason' => 'STOP',
            ]],
            'modelVersion' => 'gemini-test-model-001',
        ])]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.integrations.gemini.test'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Gemini connection succeeded using gemini-test-model-001.');

        $health = $this->getJson(route('health'));
        $this->assertStringNotContainsString('test-gemini-key', $health->getContent());
    }

    public function test_non_administrator_cannot_run_the_gemini_connection_test(): void
    {
        Http::preventStrayRequests();
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->post(route('admin.integrations.gemini.test'))
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
