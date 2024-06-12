<?php

namespace Ollico\LaravelIntelliada;

use Ollico\Intelliada\Http\Client;
use Ollico\LaravelIntelliada\Facades\Intelliada;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntelliadaServiceProvider extends PackageServiceProvider
{
    public function packageRegistered()
    {
        $this->app->singleton(Intelliada::class, function ($app) {
            return new Intelliada(
                new Client(
                    config('intelliada.token'),
                    config('intelliada.url')
                ),
                config('intelliada.key'),
            );
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-intelliada')
            ->hasConfigFile();
    }
}
