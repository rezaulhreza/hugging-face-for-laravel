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
    | These are the default models that are supported out of the box.
    | You can add your own models here.
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
];
