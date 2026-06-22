<?php

namespace Mews\LaravelPos\Facades;

use Illuminate\Support\Facades\Facade;
use Mews\LaravelPos\GatewayRegistry;
use Mews\Pos\PosInterface;

/**
 * @method static PosInterface gateway(string $bankKey)
 * @method static PosInterface[] all()
 *
 * @see GatewayRegistry
 */
class LaravelPos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GatewayRegistry::class;
    }
}
