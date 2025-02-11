<?php

namespace Rezaulhreza\HuggingFace;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HuggingFaceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hugging-face')
            ->hasConfigFile();
    }

    public function packageRegistered()
    {
        $this->app->singleton('hugging-face', function ($app) {
            return new HuggingFaceService(config('hugging-face.api_key'));
        });
    }
}
