<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HuggingFace API Key
    |--------------------------------------------------------------------------
    |
    | This is your HuggingFace API key which you can get from your
    | HuggingFace account settings. This key is used to authenticate
    | requests to the HuggingFace API.
    |
    */
    'api_key' => env('HUGGINGFACE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Models
    |--------------------------------------------------------------------------
    |
    | These are some pre-configured models for convenience. You can still use
    | any other HuggingFace model by specifying its configuration directly
    | when calling the service. Note: if it's not supported by this package you can extend the service and add your own models.
    |
    */
    'models' => [
        'CompVis/stable-diffusion-v1-4' => [
            'type' => 'image',
            'url' => 'CompVis/stable-diffusion-v1-4',
        ],
        'meta-llama/Meta-Llama-3-8B-Instruct' => [
            'type' => 'text',
            'url' => 'meta-llama/Meta-Llama-3-8B-Instruct/v1/chat/completions',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Type Mappings
    |--------------------------------------------------------------------------
    |
    | Define the default type mappings for different model tasks. This helps
    | determine how to process responses from different types of models.
    |
    */
    'model_types' => [
        'text-generation' => 'text',
        'text2text-generation' => 'text',
        'question-answering' => 'text',
        'summarization' => 'text',
        'translation' => 'text',
        'text-classification' => 'text',
        'image-classification' => 'text',
        'image-segmentation' => 'text',
        'image-to-text' => 'text',
        'text-to-image' => 'image',
        'image-to-image' => 'image',
        'visual-question-answering' => 'text',
    ],
];
