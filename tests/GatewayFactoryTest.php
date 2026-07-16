<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\EventDispatcher\EventDispatcher;
use Mews\LaravelPos\Factory\GatewayFactory;
use Mews\Pos\Gateway\AssecoPos;
use Mews\Pos\PosInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class GatewayFactoryTest extends TestCase
{
    public function test_creates_pos_interface_instance(): void
    {
        $gateway = $this->makeFactory()->create('test_bank', self::baseConfig());

        $this->assertInstanceOf(PosInterface::class, $gateway);
        $this->assertInstanceOf(AssecoPos::class, $gateway);
    }

    public function test_throws_for_non_pos_interface_gateway_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeFactory()->create(
            'test_bank',
            array_merge(self::baseConfig(), ['gateway_class' => \stdClass::class])
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     */
    public static function provide_test_mode_cases(): iterable
    {
        yield 'enabled via gateway_configs' => [
            ['gateway_configs' => ['test_mode' => true]],
            true,
        ];

        yield 'disabled by default' => [
            [],
            false,
        ];
    }

    /**
     * @dataProvider provide_test_mode_cases
     * @param array<string, mixed> $configOverrides
     */
    #[DataProvider('provide_test_mode_cases')]
    public function test_test_mode(array $configOverrides, bool $expectedTestMode): void
    {
        $gateway = $this->makeFactory()->create('test_bank', array_merge(self::baseConfig(), $configOverrides));

        $this->assertSame($expectedTestMode, $gateway->isTestMode());
    }

    private function makeFactory(): GatewayFactory
    {
        return new GatewayFactory(
            new EventDispatcher(),
            $this->createStub(LoggerInterface::class),
            $this->createStub(ClientInterface::class),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseConfig(): array
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
}
