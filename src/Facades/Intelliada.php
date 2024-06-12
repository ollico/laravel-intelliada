<?php

namespace Ollico\LaravelIntelliada\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ollico\LaravelIntelliada\Intelliada
 */
class Intelliada extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ollico\Intelliada\Intelliada::class;
    }
}
