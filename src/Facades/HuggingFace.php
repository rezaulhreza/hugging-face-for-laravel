<?php

namespace Rezaulhreza\HuggingFace\Facades;

use Illuminate\Support\Facades\Facade;
use Rezaulhreza\HuggingFace\HuggingFaceService;

/**
 * @see \Rezaulhreza\HuggingFace\HuggingFace
 */
class HuggingFace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'hugging-face';
    }
}
