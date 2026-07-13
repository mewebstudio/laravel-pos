<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\Facades\LaravelPosQuery;
use Mews\LaravelPos\LaravelPosServiceProvider;
use Mews\LaravelPos\PosQueryRegistry;
use Mews\Pos\Gateway\AkbankPos;
use Mews\Pos\Gateway\AssecoPos;
use Mews\Pos\Gateway\GarantiPos;
use Mews\Pos\Gateway\InterPos;
use Mews\Pos\Gateway\IyzicoPos;
use Mews\Pos\Gateway\KuveytPos;
use Mews\Pos\Gateway\Param3DHostPos;
use Mews\Pos\Gateway\ParamPos;
use Mews\Pos\Gateway\PayFlexCPV4Pos;
use Mews\Pos\Gateway\PayFlexV4Pos;
use Mews\Pos\Gateway\PayForPos;
use Mews\Pos\Gateway\PayTrPos;
use Mews\Pos\Gateway\PosNetPos;
use Mews\Pos\Gateway\PosNetV1Pos;
use Mews\Pos\Gateway\ToslaPos;
use Mews\Pos\Gateway\VakifKatilimPos;
use Mews\Pos\PosInterface;
use Mews\Pos\PosQuery\PosQueryInterface;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests register() with all supported banks configured.
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
        $app['config']->set('laravel-pos.banks', self::allBanksConfig());

        $app->register(LaravelPosServiceProvider::class);
    }

    public function test_pos_interface_is_bound(): void
    {
        $this->assertInstanceOf(PosInterface::class, $this->app->make(PosInterface::class));
    }

    public function test_default_gateway_is_first_bank(): void
    {
        $this->assertInstanceOf(AssecoPos::class, $this->app->make(PosInterface::class));
    }

    public function test_pos_query_interface_is_bound(): void
    {
        // First bank (AssecoPos) supports queries
        $this->assertInstanceOf(PosQueryInterface::class, $this->app->make(PosQueryInterface::class));
    }

    public function test_event_dispatcher_interface_is_bound(): void
    {
        $this->assertInstanceOf(
            \Psr\EventDispatcher\EventDispatcherInterface::class,
            $this->app->make(\Psr\EventDispatcher\EventDispatcherInterface::class)
        );
    }

    public function test_http_client_interface_is_bound(): void
    {
        $this->assertInstanceOf(
            \Psr\Http\Client\ClientInterface::class,
            $this->app->make(\Psr\Http\Client\ClientInterface::class)
        );
    }

    /** @return iterable<string, array{string, class-string<PosInterface>}> */
    public static function gatewayProvider(): iterable
    {
        foreach (self::allBanksConfig() as $bankKey => $config) {
            yield $bankKey => [$bankKey, $config['gateway_class']];
        }
    }

    #[DataProvider('gatewayProvider')]
    public function test_each_bank_resolves_correct_gateway_class(string $bankKey, string $expectedClass): void
    {
        $gateway = $this->app->make("laravel-pos:gateway:$bankKey");

        $this->assertInstanceOf(PosInterface::class, $gateway);
        $this->assertInstanceOf($expectedClass, $gateway);
    }

    public function test_all_banks_are_tagged(): void
    {
        $gateways = [...$this->app->tagged('laravel-pos:gateway')];

        $this->assertCount(16, $gateways);
    }

    public function test_all_queries_are_tagged(): void
    {
        // KuveytPos and Param3DHostPos do not support PosQuery
        $queries = [...$this->app->tagged('laravel-pos:query')];

        $this->assertCount(14, $queries);
    }

    public function test_query_resolved_by_bank_key_for_supported_gateway(): void
    {
        $this->assertInstanceOf(PosQueryInterface::class, $this->app->make('laravel-pos:query:asseco'));
    }

    public function test_query_not_registered_for_unsupported_gateway(): void
    {
        $this->assertFalse($this->app->bound('laravel-pos:query:kuveyt'));
        $this->assertFalse($this->app->bound('laravel-pos:query:param-3d-host'));
    }

    public function test_all_access_paths_return_the_same_instance(): void
    {
        $registry = $this->app->make(\Mews\LaravelPos\GatewayRegistry::class);
        $viaKey   = $this->app->make('laravel-pos:gateway:asseco');
        $tagged   = [...$this->app->tagged('laravel-pos:gateway')];

        $this->assertSame($viaKey, $registry->gateway('asseco'));
        $this->assertSame($viaKey, $tagged[0]);
    }

    public function test_query_registry_singletons_match_tagged_instances(): void
    {
        $registry = $this->app->make(PosQueryRegistry::class);
        $viaKey   = $this->app->make('laravel-pos:query:asseco');

        $this->assertSame($viaKey, $registry->query('asseco'));
    }

    public function test_laravel_pos_query_facade_resolves_pos_query_interface(): void
    {
        LaravelPosQuery::setFacadeApplication($this->app);

        $this->assertInstanceOf(PosQueryInterface::class, LaravelPosQuery::query('asseco'));
    }

    public function test_laravel_pos_query_facade_returns_same_instance_as_registry(): void
    {
        LaravelPosQuery::setFacadeApplication($this->app);

        $this->assertSame(
            $this->app->make(PosQueryRegistry::class)->query('akbank'),
            LaravelPosQuery::query('akbank'),
        );
    }

    public function test_config_is_publishable(): void
    {
        $paths = LaravelPosServiceProvider::pathsToPublish(LaravelPosServiceProvider::class, 'laravel-pos');

        $this->assertNotEmpty($paths);
        $sourceFiles = array_map('basename', array_keys($paths));
        $this->assertContains('laravel-pos.php', $sourceFiles);
    }

    /** @return array<string, array<string, mixed>> */
    private static function allBanksConfig(): array
    {
        return [
            'asseco'        => [
                'gateway_class'     => AssecoPos::class,
                'credentials'       => [
                    'merchant_id'   => '700655000200',
                    'user_name'     => 'ISBANKAPI',
                    'user_password' => 'ISBANK07',
                    'secret_key'    => 'TRPS0200',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://entegrasyon.asseco-see.com.tr/fim/api',
                    'gateway_3d'  => 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate',
                ],
                'gateway_configs'   => [],
            ],
            'akbank'        => [
                'gateway_class'     => AkbankPos::class,
                'credentials'       => [
                    'merchant_id' => '2023090417500272654BD9A49CF07574',
                    'terminal_id' => '2023090417500284633D137A249DBBEB',
                    'secret_key'  => 'c1PPl+2rNNBB2LwmQe9SrGHKa3XJYiCEFMBOd1l3244=',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://apipre.akbank.com/api/v1/payment/akbankpay',
                ],
                'gateway_configs'   => [],
            ],
            'garanti'       => [
                'gateway_class'     => GarantiPos::class,
                'credentials'       => [
                    'merchant_id'          => '7000679',
                    'user_name'            => 'PROVAUT',
                    'user_password'        => '123qweASD',
                    'terminal_id'          => '30691298',
                    'secret_key'           => '12345678',
                    'refund_user_name'     => 'PROVRFN',
                    'refund_user_password' => '123qweASD',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://sanalposprovtest.garanti.com.tr/VPServlet',
                    'gateway_3d'  => 'https://sanalposprovtest.garanti.com.tr/servlet/gt3dengine',
                ],
                'gateway_configs'   => [],
            ],
            'inter'         => [
                'gateway_class'     => InterPos::class,
                'credentials'       => [
                    'merchant_id'   => '3123',
                    'user_name'     => 'InterTestApi',
                    'user_password' => '3',
                    'secret_key'    => 'gDg1N',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://test.inter-vpos.com.tr/mpi/Default.aspx',
                    'gateway_3d'  => 'https://test.inter-vpos.com.tr/mpi/3DHost.aspx',
                ],
                'gateway_configs'   => [],
            ],
            'iyzico'        => [
                'gateway_class'     => IyzicoPos::class,
                'credentials'       => [
                    'merchant_id' => 'sandbox-api-key',
                    'secret_key'  => 'sandbox-secret-key',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://sandbox-api.iyzipay.com',
                ],
                'gateway_configs'   => [],
            ],
            'kuveyt'        => [
                'gateway_class'     => KuveytPos::class,
                'credentials'       => [
                    'merchant_id' => '496',
                    'user_name'   => 'apitest',
                    'terminal_id' => '4961',
                    'secret_key'  => 'api123',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home',
                ],
                'gateway_configs'   => [],
            ],
            'param'         => [
                'gateway_class'     => ParamPos::class,
                'credentials'       => [
                    'merchant_id'   => '10738',
                    'user_name'     => 'Test',
                    'user_password' => 'Test',
                    'secret_key'    => '0c13d406-873b-403b-9c09-a5766840d98c',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://test.param.com.tr/Pos/Bankacart/service_turkpos.asmx',
                ],
                'gateway_configs'   => [],
            ],
            'param-3d-host' => [
                'gateway_class'     => Param3DHostPos::class,
                'credentials'       => [
                    'merchant_id'   => '10738',
                    'user_name'     => 'Test',
                    'user_password' => 'Test',
                    'secret_key'    => '0c13d406-873b-403b-9c09-a5766840d98c',
                ],
                'gateway_endpoints' => [
                    'payment_api'     => 'https://test.param.com.tr/Pos/Bankacart/Service_Odeme.asmx',
                    'gateway_3d_host' => 'https://test.param.com.tr/default.aspx',
                ],
                'gateway_configs'   => [],
            ],
            'payflexv4'     => [
                'gateway_class'     => PayFlexV4Pos::class,
                'credentials'       => [
                    'merchant_id'   => 'M001',
                    'user_password' => 'P001',
                    'terminal_id'   => 'VP000579',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://onlineodemetest.vakifbank.com.tr:4443/VposService/v3/Vposreq.aspx',
                    'gateway_3d'  => 'https://3dsecuretest.vakifbank.com.tr:4443/MPIAPI/MPI_Enrollment.aspx',
                ],
                'gateway_configs'   => [],
            ],
            'payflexcpv4'   => [
                'gateway_class'     => PayFlexCPV4Pos::class,
                'credentials'       => [
                    'merchant_id'   => 'M001',
                    'user_password' => 'P001',
                    'terminal_id'   => 'VP000579',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://cptest.vakifbank.com.tr/CommonPayment/api',
                ],
                'gateway_configs'   => [],
            ],
            'payfor'        => [
                'gateway_class'     => PayForPos::class,
                'credentials'       => [
                    'merchant_id'   => '085300000009704',
                    'user_name'     => 'QNB_API_KULLANICI_3DPAY',
                    'user_password' => 'UcBN0',
                    'secret_key'    => '12345678',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://vpostest.qnbfinansbank.com/Gateway/XMLGate.aspx',
                    'gateway_3d'  => 'https://vpostest.qnbfinansbank.com/Gateway/Default.aspx',
                ],
                'gateway_configs'   => [],
            ],
            'paytr'         => [
                'gateway_class'     => PayTrPos::class,
                'credentials'       => [
                    'merchant_id'   => '123456',
                    'user_password' => 'merchant-salt',
                    'secret_key'    => 'merchant-key',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://www.paytr.com/odeme/api',
                ],
                'gateway_configs'   => [],
            ],
            'posnet'        => [
                'gateway_class'     => PosNetPos::class,
                'credentials'       => [
                    'merchant_id' => '6701950031',
                    'terminal_id' => '67540050',
                    'user_name'   => '27426',
                    'secret_key'  => '10,10,10,10,10,10,10,10',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://setmpos.ykb.com/PosnetWebService/XML',
                    'gateway_3d'  => 'https://setmpos.ykb.com/3DSWebService/YKBPaymentService',
                ],
                'gateway_configs'   => [],
            ],
            'posnetv1'      => [
                'gateway_class'     => PosNetV1Pos::class,
                'credentials'       => [
                    'merchant_id' => '6701950031',
                    'terminal_id' => '67540050',
                    'user_name'   => '27426',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://posnet.yapikredi.com.tr/PosnetWebService/XML',
                    'gateway_3d'  => 'https://posnet.yapikredi.com.tr/3DSWebService/YKBPaymentService',
                ],
                'gateway_configs'   => [],
            ],
            'tosla'         => [
                'gateway_class'     => ToslaPos::class,
                'credentials'       => [
                    'merchant_id' => '1000000494',
                    'user_name'   => 'POS_ENT_APISI_KULLANICI',
                    'secret_key'  => '33333',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://entegrasyon.tosla.com/api/Payment',
                ],
                'gateway_configs'   => [],
            ],
            'vakif-katilim' => [
                'gateway_class'     => VakifKatilimPos::class,
                'credentials'       => [
                    'merchant_id' => '1',
                    'user_name'   => 'apitest',
                    'terminal_id' => '1',
                    'secret_key'  => 'api123',
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://boa.vakifkatilim.com.tr/VirtualPOS.Gateway/Home',
                ],
                'gateway_configs'   => [],
            ],
        ];
    }
}
