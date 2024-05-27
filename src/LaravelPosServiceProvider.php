<?php

namespace Mews\LaravelPos;

use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Mews\LaravelPos\EventDispatcher\EventDispatcher;
use Mews\LaravelPos\Factory\GatewayFactory;
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
        $banks = config('laravel-pos.banks');
        if (null === $banks) {
            return;
        }

        $this->app->singletonIf(EventDispatcherInterface::class, function (Application $app) {
            return new EventDispatcher();
        });
        $this->app->singletonIf(ClientInterface::class, function (Application $app) {
            return Psr18ClientDiscovery::find();
            // return Http::buildClient(); // this one gives Undefined array key "laravel_data" error when we make HTTP request
        });

        $i = 0;
        foreach ($banks as $bankKey => $bankConfig) {
            $id = "laravel-pos:gateway:$bankKey";
            $this->app->singleton($id, function(Application $app) use ($bankKey, $bankConfig) {
                return GatewayFactory::create(
                    $bankKey,
                    $bankConfig,
                    $app->make(EventDispatcherInterface::class),
                    $app->make(LoggerInterface::class),
                    $app->make(ClientInterface::class),
                );
            });
            if ($i === 0) {
                // set default to inject for PosInterface
                $this->app->singleton(PosInterface::class, $id);
                $i++;
            }
            $this->app->tag($id, 'laravel-pos:gateway');
        }
    }
}
