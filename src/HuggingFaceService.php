<?php

namespace Rezaulhreza\HuggingFace;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HuggingFaceService
{
    protected array $models;

    protected array $modelTypes;

    public function __construct(
        protected readonly string $apiToken,
        protected readonly string $baseUrl = 'https://api-inference.huggingface.co/models/'
    ) {
        if (empty(trim($this->apiToken))) {
            throw new InvalidArgumentException('HuggingFace API token cannot be empty');
        }

        $this->models = config('hugging-face.models', []);
        $this->modelTypes = config('hugging-face.model_types', []);
    }

    /**
     * Get response from any HuggingFace model
     *
     * @param  string  $prompt  The input prompt or data
     * @param  string  $model  The model identifier
     * @param  array  $options  Additional options for the request
     * @return array|string|null Response data, base64 image string for images, or null on failure
     */
    public function getResponse(string $prompt, string $model, array $options = []): array|string|null
    {
        try {
            $modelConfig = $this->resolveModelConfig($model, $options);

            if (empty($prompt)) {
                throw new InvalidArgumentException('Prompt cannot be empty');
            }

            $payload = $this->buildPayload($prompt, $options);

            $response = Http::withToken($this->apiToken)
                ->timeout(30)
                ->retry(2, 1000)
                ->post($this->baseUrl . $model, $payload);

            if ($response->failed()) {
                $this->handleError($response);

                return null;
            }

            return $this->processResponse($response, $modelConfig['type']);
        } catch (\Throwable $e) {
            $this->logException($e);

            return null;
        }
    }

    protected function logException(\Throwable $e): void
    {
        logger()->error('HuggingFace Service Error', [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Handle API errors
     */
    protected function handleError(Response $response): void
    {
        $statusCode = $response->status();
        $errorData = [
            'status_code' => $statusCode,
            'error' => $response->json()['error'] ?? 'Unknown error',
            'response_body' => $response->body(),
            'request_url' => $response->effectiveUri()?->__toString(),
            'request_method' => $response->transferStats?->getRequest()?->getMethod(),
        ];

        logger()->error('HuggingFace API Error', $errorData);

        match ($statusCode) {
            401 => throw new \RuntimeException('Invalid or expired API token: ' . $errorData['error'], 401),
            429 => throw new \RuntimeException('Rate limit exceeded: ' . $errorData['error'], 429),
            500 => throw new \RuntimeException('HuggingFace service is unavailable: ' . $errorData['error'], 500),
            default => throw new \RuntimeException("API request failed with status {$statusCode}: " . $errorData['error'], $statusCode)
        };
    }

    /**
     * Build the request payload based on the prompt and options
     */
    protected function buildPayload(string $prompt, array $options): array
    {
        $payload = ['inputs' => $prompt];

        // Add any additional parameters from options
        if (isset($options['parameters'])) {
            $payload = array_merge($payload, $options['parameters']);
        }

        return $payload;
    }

    /**
     * Process the response based on the model type
     */
    protected function processResponse(Response $response, string $type): array|string|null
    {
        try {
            if ($type === 'image') {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }

            $data = $response->json();

            if (!$data) {
                return [
                    'text' => $response->body(),
                    'raw' => $data,
                ];
            }

            // Handle chat completion format
            if (isset($data['choices'][0]['message']['content'])) {
                return [
                    'text' => $data['choices'][0]['message']['content'],
                    'raw' => $data,
                ];
            }

            // Handle array response format
            if (isset($data[0])) {
                $firstResult = $data[0];

                return [
                    'text' => $firstResult['generated_text']
                        ?? $firstResult['answer']
                        ?? $firstResult['translation_text']
                        ?? $firstResult['summary_text']
                        ?? json_encode($firstResult),
                    'raw' => $data,
                ];
            }

            // Handle object response format
            return [
                'text' => $data['generated_text']
                    ?? $data['answer']
                    ?? $data['translation_text']
                    ?? $data['summary_text']
                    ?? json_encode($data),
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve the model configuration
     */
    protected function resolveModelConfig(string $model, array $options): array
    {
        // Check if it's a pre-configured model
        if (isset($this->models[$model])) {
            return $this->models[$model];
        }

        // If type is provided in options, use it
        if (isset($options['type'])) {
            return [
                'type' => $options['type'],
                'url' => $model,
            ];
        }

        try {
            // Try to fetch model info from HuggingFace
            $modelInfo = Http::withToken($this->apiToken)
                ->get("https://huggingface.co/api/models/{$model}")
                ->json();

            $taskType = $modelInfo['pipeline_tag'] ?? null;

            return [
                'type' => $this->determineModelType($taskType),
                'url' => $model,
            ];
        } catch (\Throwable $e) {
            // Default to text type if we can't determine the type
            return [
                'type' => 'text',
                'url' => $model,
            ];
        }
    }

    /**
     * Determine the model type based on the task
     */
    protected function determineModelType(?string $taskType): string
    {
        if ($taskType && isset($this->modelTypes[$taskType])) {
            return $this->modelTypes[$taskType];
        }

        return 'text';
    }

    /**
     * Check if a model is supported
     */
    public function isModelSupported(string $model): bool
    {
        return array_key_exists($model, $this->models);
    }
}
