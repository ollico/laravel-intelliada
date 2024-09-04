<?php

declare(strict_types=1);

namespace Ollico\LaravelIntelliada\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ollico\LaravelIntelliada\Intelliada
 */
class Intelliada extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ollico\LaravelIntelliada\Intelliada::class;
    }
}
