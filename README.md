# HuggingFace for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rezaulhreza/hugging-face-for-laravel.svg?style=flat-square)](https://packagist.org/packages/rezaulhreza/hugging-face-for-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/rezaulhreza/hugging-face-for-laravel/run-tests?label=tests)](https://github.com/rezaulhreza/hugging-face-for-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rezaulhreza/hugging-face-for-laravel.svg?style=flat-square)](https://packagist.org/packages/rezaulhreza/hugging-face-for-laravel)

A Laravel package that provides seamless integration with HuggingFace AI models for text generation and image creation.

## Installation

Install the package via composer:
```bash
composer require rezaulhreza/hugging-face-for-laravel
```

You can publish the config file with:
```bash
php artisan vendor:publish --tag="hugging-face-config"
```

## Configuration

Add your HuggingFace API key to your `.env` file:

```env
HUGGINGFACE_API_KEY=your-api-key-here
```

## Usage

### Text Generation

```php
use Rezaulhreza\HuggingFace\Facades\HuggingFace;
// Generate text
$response = HuggingFace::getResponse(
prompt: "What is artificial intelligence?",
model: "meta-llama/Meta-Llama-3-8B-Instruct"
);
```

// Access the generated text
```php
$generatedText = $response['text'];
```

### Image Generation

```php
// Generate image
$response = HuggingFace::getResponse(
prompt: "A beautiful sunset",
model: "CompVis/stable-diffusion-v1-4"
);
```

Response will contain a base64 encoded image string.

### Custom Model Types

When using a model that's not pre-configured, you can specify the model type:

```php
$response = HuggingFace::getResponse(
prompt: "What is artificial intelligence?",
model: "custom-model",
type: "text",
options: ['type' => 'text']
);
```

## Error Handling

The package includes comprehensive error handling. Failed requests will return `null` and log the error details. You can catch specific exceptions:

```php
try {
$response = HuggingFace::getResponse(prompt: "What is artificial intelligence?", model: "unknown-model");
} catch (\Exception $e) {
// Handle the exception
}
```

## Supported Model Types

The package supports various model types out of the box:

- Text Generation (`text-generation`)
- Text-to-Text Generation (`text2text-generation`)
- Question Answering (`question-answering`)
- Summarization (`summarization`)
- Translation (`translation`)
- Text Classification (`text-classification`)
- Image Classification (`image-classification`)
- Image Segmentation (`image-segmentation`)
- Image-to-Text (`image-to-text`)
- Text-to-Image (`text-to-image`)
- Image-to-Image (`image-to-image`)
- Visual Question Answering (`visual-question-answering`)

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
