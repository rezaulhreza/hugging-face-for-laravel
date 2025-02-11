<?php

namespace Rezaulhreza\HuggingFace\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Rezaulhreza\HuggingFace\HuggingFaceService
 */
class HuggingFace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'hugging-face';
    }
}
