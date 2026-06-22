<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\LaravelPosServiceProvider;
use Mews\Pos\Gateways\EstPos;
use Mews\Pos\Gateways\GarantiPos;
use Mews\Pos\PosInterface;

use Orchestra\Testbench\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;

/**
 * Tests register() with two banks configured.
 *
 * The provider is registered manually in defineEnvironment (after config is set)
 * because register() reads config eagerly — before testbench's normal provider
 * boot phase would have set it.
 */
class LaravelPosServiceProviderTest extends TestCase
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

    public function test_pos_interface_is_bound(): void
    {
        $this->assertInstanceOf(PosInterface::class, $this->app->make(PosInterface::class));
    }

    public function test_default_gateway_is_first_bank(): void
    {
        $this->assertInstanceOf(EstPos::class, $this->app->make(PosInterface::class));
    }

    public function test_event_dispatcher_interface_is_bound(): void
    {
        $this->assertInstanceOf(
            EventDispatcherInterface::class,
            $this->app->make(EventDispatcherInterface::class)
        );
    }

    public function test_http_client_interface_is_bound(): void
    {
        $this->assertInstanceOf(
            ClientInterface::class,
            $this->app->make(ClientInterface::class)
        );
    }

    public function test_gateway_resolved_by_bank_key(): void
    {
        $this->assertInstanceOf(PosInterface::class, $this->app->make('laravel-pos:gateway:est_bank'));
        $this->assertInstanceOf(PosInterface::class, $this->app->make('laravel-pos:gateway:garanti_bank'));
    }

    public function test_each_bank_key_resolves_its_own_gateway_class(): void
    {
        $this->assertInstanceOf(EstPos::class, $this->app->make('laravel-pos:gateway:est_bank'));
        $this->assertInstanceOf(GarantiPos::class, $this->app->make('laravel-pos:gateway:garanti_bank'));
    }

    public function test_all_banks_are_tagged(): void
    {
        $gateways = iterator_to_array($this->app->tagged('laravel-pos:gateway'));

        $this->assertCount(2, $gateways);
    }

    public function test_all_access_paths_return_the_same_instance(): void
    {
        $registry = $this->app->make(\Mews\LaravelPos\GatewayRegistry::class);
        $viaKey   = $this->app->make('laravel-pos:gateway:est_bank');
        $tagged   = iterator_to_array($this->app->tagged('laravel-pos:gateway'));

        $this->assertSame($viaKey, $registry->gateway('est_bank'));
        $this->assertSame($viaKey, $tagged[0]);
    }

    public function test_config_is_publishable(): void
    {
        $paths = LaravelPosServiceProvider::pathsToPublish(LaravelPosServiceProvider::class, 'laravel-pos');

        $this->assertNotEmpty($paths);
        $sourceFiles = array_map('basename', array_keys($paths));
        $this->assertContains('laravel-pos.php', $sourceFiles);
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
