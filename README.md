# HuggingFace for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rezaulhreza/hugging-face-for-laravel.svg?style=flat-square)](https://packagist.org/packages/rezaulhreza/hugging-face-for-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/rezaulhreza/hugging-face-for-laravel/run-tests?label=tests)](https://github.com/rezaulhreza/hugging-face-for-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rezaulhreza/hugging-face-for-laravel.svg?style=flat-square)](https://packagist.org/packages/rezaulhreza/hugging-face-for-laravel)

A Laravel package for easy integration with HuggingFace AI models.

## Installation

You can install the package via composer:
```bash
composer require rezaulhreza/hugging-face-for-laravel
```

You can publish the config file with:
```bash
php artisan vendor:publish --tag="hugging-face-config"
```


## Usage
```php
use Rezaulhreza\HuggingFace\Facades\HuggingFace;
// Generate text
$response = HuggingFace::getResponse(
prompt: "What is AI?",
model: "meta-llama/Meta-Llama-3-8B-Instruct"
);
// Generate image
$response = HuggingFace::getResponse(
prompt: "A beautiful sunset",
model: "CompVis/stable-diffusion-v1-4"
);
```

## Testing
```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
