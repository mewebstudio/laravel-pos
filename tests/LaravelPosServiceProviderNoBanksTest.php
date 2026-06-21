<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\LaravelPosServiceProvider;
use Mews\Pos\PosInterface;
use Orchestra\Testbench\TestCase;

class LaravelPosServiceProviderNoBanksTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-pos.banks', null);
        $app->register(LaravelPosServiceProvider::class);
    }

    public function test_pos_interface_is_not_bound_when_banks_is_null(): void
    {
        $this->assertFalse($this->app->bound(PosInterface::class));
    }

    public function test_no_gateways_tagged_when_banks_is_null(): void
    {
        $gateways = iterator_to_array($this->app->tagged('laravel-pos:gateway'));

        $this->assertEmpty($gateways);
    }
}
