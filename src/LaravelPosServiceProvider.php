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
 * @phpstan-import-type BankConfig from GatewayFactory
 */
class LaravelPosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/laravel-pos.php' => config_path('laravel-pos.php'),
        ], 'laravel-pos');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singletonIf(EventDispatcherInterface::class, fn () => new EventDispatcher());
        $this->app->singletonIf(ClientInterface::class, fn () => Psr18ClientDiscovery::find());

        $this->app->singleton(GatewayRegistry::class, function (Application $app) {
            return new GatewayRegistry(
                $this->banksConfig(),
                new GatewayFactory(
                    $app->make(EventDispatcherInterface::class),
                    $app->make(LoggerInterface::class),
                    $app->make(ClientInterface::class),
                ),
            );
        });

        $this->app->singleton(PosQueryRegistry::class, function (Application $app) {
            $queryBanks = array_filter(
                $this->banksConfig(),
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

        $banks = $this->banksConfig();
        if ([] === $banks) {
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

    /**
     * @phpstan-param BankConfig $bankConfig
     */
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

    /**
     * @phpstan-return array<non-empty-string, BankConfig>
     */
    private function banksConfig(): array
    {
        /** @phpstan-var array<non-empty-string, BankConfig> */
        return config('laravel-pos.banks') ?? [];
    }
}
