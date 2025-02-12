<?php

namespace Rezaulhreza\HuggingFace\Tests;

use Illuminate\Support\Facades\Http;
use Rezaulhreza\HuggingFace\HuggingFaceService;

class HuggingFaceServiceTest extends TestCase
{
    protected HuggingFaceService $huggingFaceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->huggingFaceService = new HuggingFaceService('fake-api-token');
    }

    public function test_get_response_with_supported_model(): void
    {
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'Hello!']]]]),
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions' => Http::response([
                'generated_text' => 'Hello!',
            ]),
        ]);

        $response = $this->huggingFaceService->getResponse(prompt: 'Hello!', model: 'meta-llama/Meta-Llama-3-8B-Instruct');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'meta-llama/Meta-Llama-3-8B-Instruct');
        });

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
        $this->assertEquals('Hello!', $response['text']);
    }

    public function test_get_response_with_dynamic_model(): void
    {
        // Mock the model info API call
        Http::fake([
            'huggingface.co/api/models/bert-base-uncased' => Http::response([
                'pipeline_tag' => 'text-classification',
            ]),
            'api-inference.huggingface.co/models/bert-base-uncased' => Http::response([
                [
                    'generated_text' => 'This is a response',
                ],
            ]),
        ]);

        $response = $this->huggingFaceService->getResponse(
            prompt: 'Classify this text',
            model: 'bert-base-uncased'
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'huggingface.co/api/models/bert-base-uncased');
        });

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
        $this->assertEquals('This is a response', $response['text']);
    }

    public function test_get_response_with_custom_model_type()
    {
        Http::fake([
            'api-inference.huggingface.co/models/custom-model' => Http::response([
                'generated_output' => 'Custom response',
            ]),
        ]);

        $response = $this->huggingFaceService->getResponse(
            prompt: 'Test prompt',
            model: 'custom-model',
            options: ['type' => 'text']
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'custom-model');
        });

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
    }

    public function test_get_response_with_image_model(): void
    {
        $fakeImageData = 'fake-binary-image-data';

        Http::fake([
            'api-inference.huggingface.co/models/stabilityai/stable-diffusion-xl-base-1.0' => Http::response($fakeImageData),
        ]);

        $response = $this->huggingFaceService->getResponse(
            prompt: 'A beautiful sunset',
            model: 'stabilityai/stable-diffusion-xl-base-1.0',
            options: ['type' => 'image']
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'stabilityai/stable-diffusion-xl-base-1.0');
        });

        $this->assertIsString($response);
        $this->assertStringStartsWith('data:image/png;base64,', $response);
    }

    public function test_model_info_fetch_failure_defaults_to_text(): void
    {
        Http::fake([
            'huggingface.co/api/models/*' => Http::response(null, 404),
            'api-inference.huggingface.co/models/unknown-model' => Http::response([
                'generated_text' => 'Response from unknown model',
            ]),
        ]);

        $response = $this->huggingFaceService->getResponse(prompt: 'Test', model: 'unknown-model');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
    }

    public function test_get_response_with_previous_messages(): void
    {
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct' => Http::response([
                'generated_text' => 'Hi there!',
            ]),
        ]);

        $previousMessages = [
            ['role' => 'user', 'content' => 'What is your name?'],
            ['role' => 'assistant', 'content' => 'I am an AI.'],
        ];

        $response = $this->huggingFaceService->getResponse(prompt: 'Hello!', model: 'meta-llama/Meta-Llama-3-8B-Instruct', options: [
            'parameters' => [
                'messages' => $previousMessages,
            ],
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
        $this->assertEquals('Hi there!', $response['text']);
    }

    public function test_get_response_with_failed_http_request(): void
    {
        Http::fake([
            'api-inference.huggingface.co/models/*' => Http::response(['error' => 'Not Found'], 404),
        ]);

        $response = $this->huggingFaceService->getResponse(prompt: 'Hello!', model: 'any-model');
        $this->assertNull($response);
    }

    public function test_is_model_supported()
    {
        $this->assertTrue($this->huggingFaceService->isModelSupported('CompVis/stable-diffusion-v1-4'));
        $this->assertFalse($this->huggingFaceService->isModelSupported('unsupported/model'));
    }

    public function test_get_response_with_malformed_payload(): void
    {
        // Mock the API to return an error response for malformed requests
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions' => Http::response(['error' => 'Invalid input'], 400),
        ]);

        $response = $this->huggingFaceService->getResponse(prompt: '', model: 'meta-llama/Meta-Llama-3-8B-Instruct'); // Sending empty prompt
        $this->assertNull($response);
    }
}
