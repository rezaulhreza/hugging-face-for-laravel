<?php

namespace Rezaulhreza\HuggingFace;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HuggingFaceService
{
    protected array $models;

    public function __construct(
        protected readonly string $apiToken,
        protected readonly string $baseUrl = 'https://api-inference.huggingface.co/models/'
    ) {
        if (empty(trim($this->apiToken))) {
            throw new InvalidArgumentException('HuggingFace API token cannot be empty');
        }

        $this->models = config()->get('hugging-face.models') ?? throw new InvalidArgumentException(
            'HuggingFace models configuration is missing'
        );
    }

    /**
     * Get response from HuggingFace model based on the input prompt
     *
     * @param  array  $options  Additional options for the model
     *
     * @throws InvalidArgumentException
     */
    public function getResponse(string $prompt, string $model, array $options = []): array|string|null
    {
        try {
            $this->validateModel($model);
            $this->validatePrompt($prompt);

            $modelConfig = $this->models[$model];
            $url = $modelConfig['url'] ?? throw new InvalidArgumentException("Invalid model configuration for: {$model}");
            $payload = $this->preparePayload($model, $prompt, $options);

            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiToken}",
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl.$url, $payload);

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

    protected function validateModel(string $model): void
    {
        if (! $this->isModelSupported($model)) {
            throw new InvalidArgumentException("Unsupported model: {$model}");
        }
    }

    protected function validatePrompt(string $prompt): void
    {
        if (empty(trim($prompt))) {
            // throw new InvalidArgumentException('Prompt cannot be empty');
            logger()->error('HuggingFace Service Error', [
                'exception' => InvalidArgumentException::class,
                'message' => 'Prompt cannot be empty',
            ]);
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
            401 => throw new \RuntimeException('Invalid or expired API token: '.$errorData['error'], 401),
            429 => throw new \RuntimeException('Rate limit exceeded: '.$errorData['error'], 429),
            500 => throw new \RuntimeException('HuggingFace service is unavailable: '.$errorData['error'], 500),
            default => throw new \RuntimeException("API request failed with status {$statusCode}: ".$errorData['error'], $statusCode)
        };
    }

    /**
     * Prepare payload based on the model type
     */
    protected function preparePayload(string $model, string $prompt, array $options): array
    {
        if ($model === 'meta-llama/Meta-Llama-3-8B-Instruct') {
            return $this->buildLlamaPayload($prompt, $options);
        }

        return $this->buildDefaultPayload($prompt, $options);
    }

    protected function buildLlamaPayload(string $prompt, array $options): array
    {
        $messages = $this->getMessages($prompt, $options['previous_message'] ?? []);

        return [
            'model' => 'meta-llama/Meta-Llama-3-8B-Instruct',
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 500,
            'stream' => $options['stream'] ?? false,
        ];
    }

    protected function buildDefaultPayload(string $prompt, array $options): array
    {
        return [
            'inputs' => $prompt,
            'options' => $options,
        ];
    }

    protected function getMessages(string $prompt, array $previousMessages): array
    {
        // Start with the current user prompt
        $messages = [['role' => 'user', 'content' => $prompt]];

        // Merge previous messages if they exist and are valid
        if ($this->areMetaLlamaPreviousMessagesValid($previousMessages)) {
            $messages = array_merge($previousMessages, $messages);
        }

        return $messages;
    }

    /**
     * Check if all messages have the required structure
     */
    protected function areMetaLlamaPreviousMessagesValid(array $messages): bool
    {
        return array_reduce($messages, fn ($carry, $message) => $carry && isset($message['role'], $message['content']), true);
    }

    /**
     * Process the response based on the model type
     */
    protected function processResponse(Response $response, string $modelType): array|string|null
    {
        return match ($modelType) {
            'image' => $this->processImageResponse($response),
            'text' => $this->processTextResponse($response),
            default => null,
        };
    }

    /**
     * Process image generation response
     */
    protected function processImageResponse(Response $response): ?string
    {
        return $response->body() ? 'data:image/png;base64,'.base64_encode($response->body()) : null;
    }

    protected function processTextResponse(Response $response): ?array
    {
        try {
            return [
                'text' => $this->extractTextFromResponse($response->json()),
                'raw_response' => $response->json(),
            ];
        } catch (\Throwable) {
            logger()->error('processTextResponse failed');

            return null;
        }
    }

    /**
     * Extract the text content from the response based on the model structure.
     */
    protected function extractTextFromResponse(array $data): ?string
    {
        if ($text = $this->extractMetaLlamaResponse($data)) {
            return $text;
        }

        if ($text = $this->extractStandardTextResponse($data)) {
            return $text;
        }

        return json_encode($data);
    }

    /**
     * Extract text for Meta Llama response format.
     */
    protected function extractMetaLlamaResponse(array $data): ?string
    {
        return Arr::get($data, 'choices.0.message.content');
    }

    /**
     * Extract text for standard text response format.
     */
    protected function extractStandardTextResponse(array $data): ?string
    {
        return Arr::get($data, '0.generated_text') ?? (is_string($data) ? $data : null);
    }

    /**
     * Check if a model is supported
     */
    public function isModelSupported(string $model): bool
    {
        return array_key_exists($model, $this->models);
    }
}
