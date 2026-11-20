<?php

namespace Intelliada\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Intelliada\Laravel\Console\InstallCommand;
use Intelliada\Laravel\Http\Controllers\EventController;
use Intelliada\Laravel\Http\Controllers\ScriptController;

class IntelliadaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/intelliada.php', 'intelliada');

        $this->app->singleton(ScriptTag::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();

        Blade::directive('intelliada', fn (): string => '<?php echo app(\\'.ScriptTag::class.'::class)->render(); ?>');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/intelliada.php' => config_path('intelliada.php'),
            ], 'intelliada-config');

            $this->commands([InstallCommand::class]);
        }
    }

    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::prefix(ScriptTag::path())
            ->middleware(config('intelliada.middleware', []))
            ->group(function (): void {
                Route::get(ScriptTag::SCRIPT_FILE, ScriptController::class)->name('intelliada.script');
                Route::post(ScriptTag::EVENT_PATH, EventController::class)->name('intelliada.event');
            });
    }
}
