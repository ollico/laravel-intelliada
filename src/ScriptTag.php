<?php

namespace Intelliada\Laravel;

use Illuminate\Support\HtmlString;

/**
 * Renders the tracking script tag. Both URLs are root-relative so the
 * script and its events always stay on whichever domain served the page.
 */
class ScriptTag
{
    public const SCRIPT_FILE = 'main.js';

    public const EVENT_PATH = 'e';

    public static function path(): string
    {
        return trim((string) config('intelliada.path', 'ia'), '/');
    }

    public static function scriptFile(): string
    {
        return __DIR__.'/../resources/dist/intelliada.min.js';
    }

    public static function version(): string
    {
        return substr((string) md5_file(self::scriptFile()), 0, 10);
    }

    public function isEnabled(): bool
    {
        return (bool) config('intelliada.enabled', true) && filled(config('intelliada.token'));
    }

    public function render(): HtmlString
    {
        if (! $this->isEnabled()) {
            return new HtmlString('');
        }

        $base = '/'.self::path();

        return new HtmlString(sprintf(
            '<script defer src="%s" data-endpoint="%s"></script>',
            e($base.'/'.self::SCRIPT_FILE.'?v='.self::version()),
            e($base.'/'.self::EVENT_PATH),
        ));
    }
}
