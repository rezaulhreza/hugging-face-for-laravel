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

    public function test_get_response_with_supported_model()
    {
        // Mocking the response from the API
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'Hello!']]]]),
        ]);

        // Test with a valid model
        $response = $this->huggingFaceService->getResponse('Hello!', 'meta-llama/Meta-Llama-3-8B-Instruct');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions'
                && $request->data() === [
                    'model' => 'meta-llama/Meta-Llama-3-8B-Instruct',
                    'messages' => [['role' => 'user', 'content' => 'Hello!']],
                    'max_tokens' => 500,
                    'stream' => false,
                ];
        });

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
        $this->assertEquals('Hello!', $response['text']);
    }

    public function test_get_response_with_previous_messages()
    {
        // Mocking the response from the API
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'Hi there!']]]]),
        ]);

        // Previous messages to be sent
        $previousMessages = [
            ['role' => 'user', 'content' => 'What is your name?'],
            ['role' => 'assistant', 'content' => 'I am an AI.'],
        ];

        // Test with a valid model and previous messages
        $response = $this->huggingFaceService->getResponse('Hello!', 'meta-llama/Meta-Llama-3-8B-Instruct', [
            'previous_message' => $previousMessages,
        ]);

        Http::assertSent(function ($request) use ($previousMessages) {
            return $request->url() === 'https://api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions'
                && $request->data() === [
                    'model' => 'meta-llama/Meta-Llama-3-8B-Instruct',
                    'messages' => array_merge($previousMessages, [['role' => 'user', 'content' => 'Hello!']]),
                    'max_tokens' => 500,
                    'stream' => false,
                ];
        });

        $this->assertIsArray($response);
        $this->assertArrayHasKey('text', $response);
        $this->assertEquals('Hi there!', $response['text']);
    }

    public function test_get_response_with_unsupported_model()
    {
        $response = $this->huggingFaceService->getResponse('Hello!', 'unsupported/model');
        $this->assertNull($response);
    }

    public function test_get_response_with_failed_http_request()
    {
        // Mocking a failed response
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions' => Http::response(['error' => 'Not Found'], 404),
        ]);

        $response = $this->huggingFaceService->getResponse('Hello!', 'meta-llama/Meta-Llama-3-8B-Instruct');
        $this->assertNull($response);
    }

    public function test_is_model_supported()
    {
        $this->assertTrue($this->huggingFaceService->isModelSupported('CompVis/stable-diffusion-v1-4'));
        $this->assertFalse($this->huggingFaceService->isModelSupported('unsupported/model'));
    }

    public function test_get_response_with_malformed_payload()
    {
        // Mock the API to return an error response for malformed requests
        Http::fake([
            'api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions' => Http::response(['error' => 'Invalid input'], 400),
        ]);

        $response = $this->huggingFaceService->getResponse('', 'meta-llama/Meta-Llama-3-8B-Instruct'); // Sending empty prompt
        $this->assertNull($response);
    }
}
