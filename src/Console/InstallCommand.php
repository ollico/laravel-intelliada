<?php

namespace Intelliada\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'intelliada:install
        {--token= : Your Intelliada project token}
        {--path= : The first-party path to serve the script from (random by default)}';

    protected $description = 'Configure Intelliada and add the first-party tracking script to your layout';

    /**
     * Layouts checked, in order, for a </head> to add the script tag to.
     *
     * @var list<string>
     */
    private const LAYOUTS = [
        'views/app.blade.php',
        'views/layouts/app.blade.php',
        'views/components/layouts/app.blade.php',
    ];

    public function handle(): int
    {
        $token = $this->option('token') ?: $this->ask('Project token');

        if (! is_string($token) || trim($token) === '') {
            $this->components->error('A project token is required. Find it in your Intelliada project settings.');

            return self::FAILURE;
        }

        $path = $this->option('path') ?: $this->existingPath() ?: Str::lower(Str::random(8));

        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', (string) $path)) {
            $this->components->error('The path may only contain letters, numbers, dashes and underscores.');

            return self::FAILURE;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'intelliada-config']);
        $this->components->task('Published config/intelliada.php');

        $this->writeEnvironment(['INTELLIADA_TOKEN' => trim($token), 'INTELLIADA_PATH' => (string) $path]);
        $this->components->task('Saved INTELLIADA_TOKEN and INTELLIADA_PATH to .env');

        $layout = $this->addScriptToLayout();

        if ($layout === null) {
            $this->components->warn('No layout with a </head> found. Add @intelliada inside the <head> of your layout.');
        } else {
            $this->components->task("Added @intelliada to resources/{$layout}");
        }

        $this->components->info("Tracking is served from /{$path} on your own domain.");

        return self::SUCCESS;
    }

    private function existingPath(): ?string
    {
        $contents = $this->environmentContents();

        if (preg_match('/^INTELLIADA_PATH=(.+)$/m', $contents, $matches)) {
            return trim($matches[1], " \"'");
        }

        return null;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function writeEnvironment(array $values): void
    {
        $contents = $this->environmentContents();

        foreach ($values as $key => $value) {
            $line = $key.'='.$value;
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = (string) preg_replace($pattern, $line, $contents);

                continue;
            }

            $contents = rtrim($contents, "\n").($contents === '' ? '' : "\n").$line."\n";
        }

        file_put_contents($this->environmentFile(), $contents);
    }

    private function addScriptToLayout(): ?string
    {
        foreach (self::LAYOUTS as $layout) {
            $file = resource_path($layout);

            if (! is_file($file)) {
                continue;
            }

            $contents = (string) file_get_contents($file);

            if (str_contains($contents, '@intelliada')) {
                return $layout;
            }

            if (! str_contains($contents, '</head>')) {
                continue;
            }

            file_put_contents($file, preg_replace('/^(\s*)<\/head>/m', "$1    @intelliada\n$1</head>", $contents, 1));

            return $layout;
        }

        return null;
    }

    private function environmentFile(): string
    {
        return $this->laravel->environmentFilePath();
    }

    private function environmentContents(): string
    {
        $file = $this->environmentFile();

        return is_file($file) ? (string) file_get_contents($file) : '';
    }
}
