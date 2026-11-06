<?php

namespace Intelliada\Laravel\Tests\Feature;

use Illuminate\Support\Facades\File;
use Intelliada\Laravel\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    private string $environmentDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environmentDirectory = sys_get_temp_dir().'/intelliada-install-'.uniqid();
        File::ensureDirectoryExists($this->environmentDirectory);
        File::put($this->environmentDirectory.'/.env', "APP_NAME=Shop\nAPP_ENV=local\n");
        $this->app->useEnvironmentPath($this->environmentDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->environmentDirectory);
        File::delete([resource_path('views/app.blade.php'), config_path('intelliada.php')]);

        parent::tearDown();
    }

    public function test_install_writes_env_publishes_config_and_adds_the_script_to_the_layout(): void
    {
        File::put(resource_path('views/app.blade.php'), "<html>\n    <head>\n        <title>Shop</title>\n    </head>\n</html>\n");

        $this->artisan('intelliada:install', ['--token' => 'abc123', '--path' => 'k3x9q2'])->assertSuccessful();

        $environment = File::get($this->environmentDirectory.'/.env');
        $this->assertStringContainsString("APP_NAME=Shop\n", $environment);
        $this->assertStringContainsString("INTELLIADA_TOKEN=abc123\n", $environment);
        $this->assertStringContainsString("INTELLIADA_PATH=k3x9q2\n", $environment);

        $this->assertFileExists(config_path('intelliada.php'));

        $this->assertSame(
            "<html>\n    <head>\n        <title>Shop</title>\n        @intelliada\n    </head>\n</html>\n",
            File::get(resource_path('views/app.blade.php')),
        );
    }

    public function test_install_generates_a_random_lowercase_path(): void
    {
        $this->artisan('intelliada:install', ['--token' => 'abc123'])->assertSuccessful();

        $this->assertMatchesRegularExpression('/^INTELLIADA_PATH=[a-z0-9]{8}$/m', File::get($this->environmentDirectory.'/.env'));
    }

    public function test_reinstalling_updates_the_token_and_keeps_the_path_and_layout(): void
    {
        File::put(resource_path('views/app.blade.php'), "<head>\n</head>\n");

        $this->artisan('intelliada:install', ['--token' => 'first'])->assertSuccessful();
        $path = $this->environmentValue('INTELLIADA_PATH');

        $this->artisan('intelliada:install', ['--token' => 'second'])->assertSuccessful();

        $this->assertSame('second', $this->environmentValue('INTELLIADA_TOKEN'));
        $this->assertSame($path, $this->environmentValue('INTELLIADA_PATH'));
        $this->assertSame(1, substr_count(File::get(resource_path('views/app.blade.php')), '@intelliada'));
    }

    public function test_install_explains_how_to_add_the_script_when_no_layout_is_found(): void
    {
        $this->artisan('intelliada:install', ['--token' => 'abc123'])
            ->expectsOutputToContain('Add @intelliada inside the <head> of your layout.')
            ->assertSuccessful();
    }

    public function test_install_requires_a_token(): void
    {
        $this->artisan('intelliada:install')
            ->expectsQuestion('Project token', '')
            ->assertFailed();

        $this->assertStringNotContainsString('INTELLIADA_TOKEN', File::get($this->environmentDirectory.'/.env'));
    }

    public function test_install_rejects_an_unsafe_path(): void
    {
        $this->artisan('intelliada:install', ['--token' => 'abc123', '--path' => '../admin'])->assertFailed();
    }

    private function environmentValue(string $key): ?string
    {
        preg_match('/^'.$key.'=(.*)$/m', File::get($this->environmentDirectory.'/.env'), $matches);

        return $matches[1] ?? null;
    }
}
