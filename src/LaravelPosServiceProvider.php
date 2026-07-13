<?php

namespace Mews\LaravelPos;

use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Mews\LaravelPos\EventDispatcher\EventDispatcher;
use Mews\LaravelPos\Factory\GatewayFactory;
use Mews\LaravelPos\Factory\PosQueryFactory;
use Mews\Pos\Factory\PosQueryFactory as MewsPosPosQueryFactory;
use Mews\Pos\PosInterface;
use Mews\Pos\PosQuery\PosQueryInterface;
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

        $this->app->singleton(GatewayRegistry::class, function (Application $app) {
            return new GatewayRegistry(
                config('laravel-pos.banks') ?? [],
                new GatewayFactory(
                    $app->make(EventDispatcherInterface::class),
                    $app->make(LoggerInterface::class),
                    $app->make(ClientInterface::class),
                ),
            );
        });

        $this->app->singleton(PosQueryRegistry::class, function (Application $app) {
            $queryBanks = array_filter(
                config('laravel-pos.banks') ?? [],
                static fn (array $config) => null !== MewsPosPosQueryFactory::getPosQueryClassForGateway($config['gateway_class'])
            );

            return new PosQueryRegistry(
                $queryBanks,
                new PosQueryFactory(
                    $app->make(EventDispatcherInterface::class),
                    $app->make(LoggerInterface::class),
                    $app->make(ClientInterface::class),
                ),
            );
        });

        $banks = config('laravel-pos.banks');
        if (null === $banks || [] === $banks) {
            return;
        }

        $firstKey = array_key_first($banks);

        $gatewayId = $this->registerGateway($firstKey);
        $this->app->singleton(PosInterface::class, fn (Application $app) => $app->make($gatewayId));

        $queryId = $this->registerGatewayQuery($firstKey, $banks[$firstKey]);
        if (null !== $queryId) {
            $this->app->singleton(PosQueryInterface::class, fn (Application $app) => $app->make($queryId));
        }

        foreach (array_keys($banks) as $bankKey) {
            if ($bankKey === $firstKey) {
                continue;
            }

            $this->registerGateway($bankKey);
            $this->registerGatewayQuery($bankKey, $banks[$bankKey]);
        }
    }

    private function registerGateway(string $bankKey): string
    {
        $id = "laravel-pos:gateway:$bankKey";

        $this->app->singleton($id, fn (Application $app) => $app->make(GatewayRegistry::class)->gateway($bankKey));
        $this->app->tag($id, 'laravel-pos:gateway');

        return $id;
    }

    private function registerGatewayQuery(string $bankKey, array $bankConfig): ?string
    {
        if (null === MewsPosPosQueryFactory::getPosQueryClassForGateway($bankConfig['gateway_class'])) {
            return null;
        }

        $id = "laravel-pos:query:$bankKey";

        $this->app->singleton($id, fn (Application $app) => $app->make(PosQueryRegistry::class)->query($bankKey));
        $this->app->tag($id, 'laravel-pos:query');

        return $id;
    }
}
