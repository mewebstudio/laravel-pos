<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\LaravelPosServiceProvider;
use Mews\LaravelPos\PosQueryRegistry;
use Mews\Pos\Gateway\AssecoPos;
use Mews\Pos\Gateway\GarantiPos;
use Mews\Pos\PosQuery\AssecoPosQuery;
use Mews\Pos\PosQuery\GarantiPosQuery;
use Mews\Pos\PosQuery\PosQueryInterface;
use Orchestra\Testbench\TestCase;

class PosQueryRegistryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-pos.banks', [
            'est_bank' => $this->makeEstPosConfig(),
            'garanti_bank' => $this->makeGarantiPosConfig(),
        ]);

        $app->register(LaravelPosServiceProvider::class);
    }

    public function test_registry_is_bound(): void
    {
        $this->assertInstanceOf(PosQueryRegistry::class, $this->app->make(PosQueryRegistry::class));
    }

    public function test_pos_query_interface_is_bound_to_first_bank(): void
    {
        $this->assertInstanceOf(PosQueryInterface::class, $this->app->make(PosQueryInterface::class));
        $this->assertInstanceOf(AssecoPosQuery::class, $this->app->make(PosQueryInterface::class));
    }

    public function test_query_resolved_by_bank_key(): void
    {
        $this->assertInstanceOf(PosQueryInterface::class, $this->app->make('laravel-pos:query:est_bank'));
        $this->assertInstanceOf(PosQueryInterface::class, $this->app->make('laravel-pos:query:garanti_bank'));
    }

    public function test_each_bank_key_resolves_its_own_query_class(): void
    {
        $this->assertInstanceOf(AssecoPosQuery::class, $this->app->make('laravel-pos:query:est_bank'));
        $this->assertInstanceOf(GarantiPosQuery::class, $this->app->make('laravel-pos:query:garanti_bank'));
    }

    public function test_all_queries_are_tagged(): void
    {
        $queries = [...$this->app->tagged('laravel-pos:query')];

        $this->assertCount(2, $queries);
    }

    public function test_query_returns_same_singleton_instance(): void
    {
        $registry = $this->app->make(PosQueryRegistry::class);

        $this->assertSame($registry->query('est_bank'), $registry->query('est_bank'));
    }

    public function test_all_returns_all_registered_queries(): void
    {
        $registry = $this->app->make(PosQueryRegistry::class);

        $this->assertCount(2, $registry->all());
    }

    public function test_query_throws_for_unknown_bank_key(): void
    {
        $registry = $this->app->make(PosQueryRegistry::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unknown_bank/');

        $registry->query('unknown_bank');
    }

    public function test_registry_is_bound_even_when_no_banks_configured(): void
    {
        $app = $this->createApplication();
        $app['config']->set('laravel-pos.banks', null);
        $app->register(LaravelPosServiceProvider::class);

        $this->assertInstanceOf(PosQueryRegistry::class, $app->make(PosQueryRegistry::class));
    }

    private function makeEstPosConfig(): array
    {
        return [
            'gateway_class' => AssecoPos::class,
            'credentials' => [
                'merchant_id' => '700655000200',
                'user_name' => 'ISBANKAPI',
                'user_password' => 'ISBANK07',
                'secret_key' => 'TRPS0200',
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://entegrasyon.asseco-see.com.tr/fim/api',
                'gateway_3d' => 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate',
                'gateway_3d_host' => null,
                'query_api' => null,
            ],
            'gateway_configs' => [],
        ];
    }

    private function makeGarantiPosConfig(): array
    {
        return [
            'gateway_class' => GarantiPos::class,
            'credentials' => [
                'merchant_id' => '7000679',
                'user_name' => 'PROVAUT',
                'user_password' => '123qweASD',
                'terminal_id' => '30691298',
                'secret_key' => '12345678',
                'refund_user_name' => 'PROVRFN',
                'refund_user_password' => '123qweASD',
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://sanalposprovtest.garanti.com.tr/VPServlet',
                'gateway_3d' => 'https://sanalposprovtest.garanti.com.tr/servlet/gt3dengine',
                'gateway_3d_host' => null,
                'query_api' => null,
            ],
            'gateway_configs' => [],
        ];
    }
}
