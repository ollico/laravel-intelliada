<?php

namespace Ollico\Intelliada\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ollico\Intelliada\Intelliada
 */
class Intelliada extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ollico\Intelliada\Intelliada::class;
    }
}
