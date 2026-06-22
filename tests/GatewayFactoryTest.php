<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\EventDispatcher\EventDispatcher;
use Mews\LaravelPos\Factory\AccountFactory;
use Mews\LaravelPos\Factory\AccountFactoryInterface;
use Mews\LaravelPos\Factory\GatewayFactory;
use Mews\Pos\Entity\Account\AbstractPosAccount;
use Mews\Pos\Gateways\EstPos;
use Mews\Pos\PosInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class GatewayFactoryTest extends TestCase
{
    public function test_creates_pos_interface_instance(): void
    {
        $gateway = GatewayFactory::create(
            'test_bank',
            self::baseConfig(),
            new EventDispatcher(),
            $this->createStub(LoggerInterface::class),
            $this->createStub(ClientInterface::class),
            new AccountFactory(),
        );

        $this->assertInstanceOf(PosInterface::class, $gateway);
        $this->assertInstanceOf(EstPos::class, $gateway);
    }

    public function test_throws_for_non_pos_interface_gateway_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        GatewayFactory::create(
            'test_bank',
            array_merge(self::baseConfig(), ['gateway_class' => \stdClass::class]),
            new EventDispatcher(),
            $this->createStub(LoggerInterface::class),
            $this->createStub(ClientInterface::class),
            new AccountFactory(),
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

        yield 'enabled via legacy option' => [
            ['gateway_configs' => [], 'test_mode' => true],
            true,
        ];

        yield 'gateway_configs takes precedence over legacy' => [
            ['gateway_configs' => ['test_mode' => false], 'test_mode' => true],
            false,
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
        $gateway = GatewayFactory::create(
            'test_bank',
            array_merge(self::baseConfig(), $configOverrides),
            new EventDispatcher(),
            $this->createStub(LoggerInterface::class),
            $this->createStub(ClientInterface::class),
            new AccountFactory(),
        );

        $this->assertSame($expectedTestMode, $gateway->isTestMode());
    }

    public function test_delegates_account_creation_to_provided_factory(): void
    {
        $mockFactory = $this->createMock(AccountFactoryInterface::class);
        $mockFactory->expects($this->once())
            ->method('create')
            ->willReturn((new AccountFactory())->create(
                EstPos::class,
                'test_bank',
                self::baseConfig()['credentials'],
            ));

        GatewayFactory::create(
            'test_bank',
            self::baseConfig(),
            new EventDispatcher(),
            $this->createStub(LoggerInterface::class),
            $this->createStub(ClientInterface::class),
            $mockFactory,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseConfig(): array
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
}
