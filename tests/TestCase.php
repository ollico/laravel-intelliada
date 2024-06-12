<?php

namespace Ollico\Intelliada\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ollico\Intelliada\IntelliadaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Ollico\\Intelliada\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            IntelliadaServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-intelliada_table.php.stub';
        $migration->up();
        */
    }
}
