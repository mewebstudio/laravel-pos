<?php

namespace Mews\LaravelPos\Facades;

use Illuminate\Support\Facades\Facade;
use Mews\LaravelPos\PosQueryRegistry;
use Mews\Pos\PosQuery\PosQueryInterface;

/**
 * @method static PosQueryInterface query(string $bankKey)
 * @method static PosQueryInterface[] all()
 *
 * @see PosQueryRegistry
 */
class LaravelPosQuery extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PosQueryRegistry::class;
    }
}
