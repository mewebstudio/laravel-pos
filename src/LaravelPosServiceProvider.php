<?php

namespace Mews\LaravelPos;

use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Mews\LaravelPos\EventDispatcher\EventDispatcher;
use Mews\LaravelPos\Factory\AccountFactory;
use Mews\LaravelPos\Factory\AccountFactoryInterface;
use Mews\LaravelPos\GatewayRegistry;
use Mews\Pos\PosInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class LaravelPosServiceProvider
 * @package Mews\LaravelPos
 */
class LaravelPosServiceProvider extends ServiceProvider {

    /**
     * Boot the service provider.
     *
     * @return null
     */
    public function boot()
    {
        // Config file publishes
        $this->publishes([
            __DIR__.'/../config/laravel-pos.php' => config_path('laravel-pos.php')
        ], 'laravel-pos');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singletonIf(EventDispatcherInterface::class, fn () => new EventDispatcher());
        $this->app->singletonIf(ClientInterface::class, fn () => Psr18ClientDiscovery::find());
        $this->app->singletonIf(AccountFactoryInterface::class, AccountFactory::class);

        $this->app->singleton(GatewayRegistry::class, function (Application $app) {
            return new GatewayRegistry(
                config('laravel-pos.banks') ?? [],
                $app->make(EventDispatcherInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(ClientInterface::class),
                $app->make(AccountFactoryInterface::class),
            );
        });

        $banks = config('laravel-pos.banks');
        if (empty($banks)) {
            return;
        }

        $firstKey = array_key_first($banks);
        $this->app->singleton(PosInterface::class, function (Application $app) use ($firstKey) {
            return $app->make(GatewayRegistry::class)->gateway($firstKey);
        });

        foreach (array_keys($banks) as $bankKey) {
            $id = "laravel-pos:gateway:$bankKey";
            $this->app->singleton($id, function (Application $app) use ($bankKey) {
                return $app->make(GatewayRegistry::class)->gateway($bankKey);
            });
            $this->app->tag($id, 'laravel-pos:gateway');
        }
    }
}
