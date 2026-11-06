<?php

namespace Intelliada\Laravel\Tests;

use Illuminate\Foundation\Application;
use Intelliada\Laravel\IntelliadaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [IntelliadaServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('intelliada.token', 'project-token');
        $app['config']->set('intelliada.path', 'k3x9q2');
        $app['config']->set('intelliada.endpoint', 'https://intelliada.test/api/v1/ingest');
    }
}
