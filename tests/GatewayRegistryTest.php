<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\GatewayRegistry;
use Mews\LaravelPos\LaravelPosServiceProvider;
use Mews\Pos\Gateways\EstPos;
use Mews\Pos\Gateways\GarantiPos;
use Mews\Pos\PosInterface;
use Orchestra\Testbench\TestCase;

class GatewayRegistryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-pos.banks', [
            'est_bank'     => $this->makeEstPosConfig(),
            'garanti_bank' => $this->makeGarantiPosConfig(),
        ]);

        $app->register(LaravelPosServiceProvider::class);
    }

    public function test_registry_is_bound(): void
    {
        $this->assertInstanceOf(GatewayRegistry::class, $this->app->make(GatewayRegistry::class));
    }

    public function test_gateway_returns_pos_interface(): void
    {
        $registry = $this->app->make(GatewayRegistry::class);

        $this->assertInstanceOf(PosInterface::class, $registry->gateway('est_bank'));
    }

    public function test_gateway_returns_correct_class_per_bank_key(): void
    {
        $registry = $this->app->make(GatewayRegistry::class);

        $this->assertInstanceOf(EstPos::class, $registry->gateway('est_bank'));
        $this->assertInstanceOf(GarantiPos::class, $registry->gateway('garanti_bank'));
    }

    public function test_gateway_returns_same_singleton_instance(): void
    {
        $registry = $this->app->make(GatewayRegistry::class);

        $this->assertSame($registry->gateway('est_bank'), $registry->gateway('est_bank'));
    }

    public function test_all_returns_all_configured_gateways(): void
    {
        $registry = $this->app->make(GatewayRegistry::class);

        $this->assertCount(2, $registry->all());
    }

    public function test_all_returns_pos_interface_instances(): void
    {
        $registry = $this->app->make(GatewayRegistry::class);

        foreach ($registry->all() as $gateway) {
            $this->assertInstanceOf(PosInterface::class, $gateway);
        }
    }

    public function test_gateway_throws_for_unknown_bank_key(): void
    {
        $registry = $this->app->make(GatewayRegistry::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/unknown_bank/');

        $registry->gateway('unknown_bank');
    }

    public function test_registry_is_bound_even_when_no_banks_configured(): void
    {
        $app = $this->createApplication();
        $app['config']->set('laravel-pos.banks', null);
        $app->register(LaravelPosServiceProvider::class);

        $this->assertInstanceOf(GatewayRegistry::class, $app->make(GatewayRegistry::class));
    }

    private function makeEstPosConfig(): array
    {
        return [
            'gateway_class'     => EstPos::class,
            'lang'              => PosInterface::LANG_TR,
            'credentials'       => [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '700655000200',
                'user_name'     => 'ISBANKAPI',
                'user_password' => 'ISBANK07',
                'enc_key'       => 'TRPS0200',
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://entegrasyon.asseco-see.com.tr/fim/api',
                'gateway_3d'      => 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate',
                'gateway_3d_host' => null,
                'query_api'       => null,
            ],
            'gateway_configs'   => [],
        ];
    }

    private function makeGarantiPosConfig(): array
    {
        return [
            'gateway_class'     => GarantiPos::class,
            'lang'              => PosInterface::LANG_TR,
            'credentials'       => [
                'payment_model'        => PosInterface::MODEL_NON_SECURE,
                'merchant_id'          => '7000679',
                'user_name'            => 'PROVAUT',
                'user_password'        => '123qweASD',
                'terminal_id'          => '30691298',
                'enc_key'              => '12345678',
                'refund_user_name'     => 'PROVRFN',
                'refund_user_password' => '123qweASD',
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://sanalposprovtest.garanti.com.tr/VPServlet',
                'gateway_3d'      => 'https://sanalposprovtest.garanti.com.tr/servlet/gt3dengine',
                'gateway_3d_host' => null,
                'query_api'       => null,
            ],
            'gateway_configs'   => [],
        ];
    }
}
