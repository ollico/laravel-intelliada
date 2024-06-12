<?php

namespace Ollico\Intelliada;

use Ollico\Intelliada\Commands\IntelliadaCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntelliadaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-intelliada')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-intelliada_table')
            ->hasCommand(IntelliadaCommand::class);
    }
}
