<?php

namespace Mews\LaravelPos\Tests;

use Mews\LaravelPos\Factory\AccountFactory;
use Mews\Pos\Entity\Account\AkbankPosAccount;
use Mews\Pos\Entity\Account\EstPosAccount;
use Mews\Pos\Entity\Account\GarantiPosAccount;
use Mews\Pos\Entity\Account\InterPosAccount;
use Mews\Pos\Entity\Account\KuveytPosAccount;
use Mews\Pos\Entity\Account\ParamPosAccount;
use Mews\Pos\Entity\Account\PayFlexAccount;
use Mews\Pos\Entity\Account\PayForAccount;
use Mews\Pos\Entity\Account\PosNetAccount;
use Mews\Pos\Entity\Account\ToslaPosAccount;
use Mews\Pos\Gateways\AkbankPos;
use Mews\Pos\Gateways\EstPos;
use Mews\Pos\Gateways\EstV3Pos;
use Mews\Pos\Gateways\GarantiPos;
use Mews\Pos\Gateways\InterPos;
use Mews\Pos\Gateways\KuveytPos;
use Mews\Pos\Gateways\ParamPos;
use Mews\Pos\Gateways\PayFlexCPV4Pos;
use Mews\Pos\Gateways\PayFlexV4Pos;
use Mews\Pos\Gateways\PayForPos;
use Mews\Pos\Gateways\PosNet;
use Mews\Pos\Gateways\PosNetV1Pos;
use Mews\Pos\Gateways\ToslaPos;
use Mews\Pos\Gateways\VakifKatilimPos;
use Mews\Pos\PosInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccountFactoryTest extends TestCase
{
    /**
     * @return iterable<string, array{string, array<string, mixed>, class-string}>
     */
    public static function gatewayAccountProvider(): iterable
    {
        yield 'EstPos' => [
            EstPos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '700655000200',
                'user_name'     => 'ISBANKAPI',
                'user_password' => 'ISBANK07',
                'enc_key'       => 'TRPS0200',
            ],
            EstPosAccount::class,
        ];

        yield 'EstV3Pos' => [
            EstV3Pos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '700655000200',
                'user_name'     => 'ISBANKAPI',
                'user_password' => 'ISBANK07',
            ],
            EstPosAccount::class,
        ];

        yield 'PosNet' => [
            PosNet::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '6701950031',
                'terminal_id'   => '67540050',
                'user_name'     => '27426',
                'enc_key'       => '10,10,10,10,10,10,10,10',
            ],
            PosNetAccount::class,
        ];

        yield 'PosNetV1Pos' => [
            PosNetV1Pos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '6701950031',
                'terminal_id'   => '67540050',
                'user_name'     => '27426',
            ],
            PosNetAccount::class,
        ];

        yield 'PayForPos' => [
            PayForPos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '085300000009704',
                'user_name'     => 'QNB_API_KULLANICI_3DPAY',
                'user_password' => 'UcBN0',
                'enc_key'       => '12345678',
            ],
            PayForAccount::class,
        ];

        yield 'GarantiPos' => [
            GarantiPos::class,
            [
                'payment_model'        => PosInterface::MODEL_NON_SECURE,
                'merchant_id'          => '7000679',
                'user_name'            => 'PROVAUT',
                'user_password'        => '123qweASD',
                'terminal_id'          => '30691298',
                'enc_key'              => '12345678',
                'refund_user_name'     => 'PROVRFN',
                'refund_user_password' => '123qweASD',
            ],
            GarantiPosAccount::class,
        ];

        yield 'InterPos' => [
            InterPos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => '3123',
                'user_name'     => 'InterTestApi',
                'user_password' => '3',
                'enc_key'       => 'gDg1N',
            ],
            InterPosAccount::class,
        ];

        yield 'KuveytPos' => [
            KuveytPos::class,
            [
                'payment_model' => PosInterface::MODEL_3D_SECURE,
                'merchant_id'   => '496',
                'user_name'     => 'apitest',
                'terminal_id'   => '4961',
                'enc_key'       => 'api123',
            ],
            KuveytPosAccount::class,
        ];

        yield 'VakifKatilimPos' => [
            VakifKatilimPos::class,
            [
                'payment_model' => PosInterface::MODEL_3D_SECURE,
                'merchant_id'   => '1',
                'user_name'     => 'apitest',
                'terminal_id'   => '1',
                'enc_key'       => 'api123',
            ],
            KuveytPosAccount::class,
        ];

        yield 'PayFlexV4Pos' => [
            PayFlexV4Pos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => 'M001',
                'user_password' => 'P001',
                'terminal_id'   => 'VP000579',
            ],
            PayFlexAccount::class,
        ];

        yield 'PayFlexCPV4Pos' => [
            PayFlexCPV4Pos::class,
            [
                'payment_model' => PosInterface::MODEL_NON_SECURE,
                'merchant_id'   => 'M001',
                'user_password' => 'P001',
                'terminal_id'   => 'VP000579',
            ],
            PayFlexAccount::class,
        ];

        yield 'AkbankPos' => [
            AkbankPos::class,
            [
                'merchant_id' => '2023090417500272654BD9A49CF07574',
                'terminal_id' => '2023090417500284633D137A249DBBEB',
                'enc_key'     => 'c1PPl+2rNNBB2LwmQe9SrGHKa3XJYiCEFMBOd1l3244=',
            ],
            AkbankPosAccount::class,
        ];

        yield 'ToslaPos' => [
            ToslaPos::class,
            [
                'merchant_id' => '1000000494',
                'user_name'   => 'POS_ENT_APISI_KULLANICI',
                'enc_key'     => '33333',
            ],
            ToslaPosAccount::class,
        ];

        yield 'ParamPos' => [
            ParamPos::class,
            [
                'merchant_id'   => 10738,
                'user_name'     => 'Test',
                'user_password' => 'Test',
                'enc_key'       => '0c13d406-873b-403b-9c09-a5766840d98c',
            ],
            ParamPosAccount::class,
        ];
    }

    /**
     * @dataProvider gatewayAccountProvider
     * @param class-string $gatewayClass
     * @param array<string, mixed> $credentials
     * @param class-string $expectedAccountClass
     */
    #[DataProvider('gatewayAccountProvider')]
    public function test_creates_correct_account_type(
        string $gatewayClass,
        array  $credentials,
        string $expectedAccountClass
    ): void {
        $account = (new AccountFactory())->create($gatewayClass, 'test_bank', $credentials);

        $this->assertInstanceOf($expectedAccountClass, $account);
    }

    /** @dataProvider gatewayAccountProvider */
    #[DataProvider('gatewayAccountProvider')]
    public function test_account_bank_name_is_preserved(
        string $gatewayClass,
        array  $credentials,
        string $_expectedAccountClass
    ): void {
        $account = (new AccountFactory())->create($gatewayClass, 'my_bank', $credentials);

        $this->assertSame('my_bank', $account->getBank());
    }

    public function test_throws_domain_exception_for_unknown_gateway(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/stdClass/');

        (new AccountFactory())->create(\stdClass::class, 'test_bank', [
            'payment_model' => PosInterface::MODEL_NON_SECURE,
        ]);
    }

    public function test_account_lang_is_passed_through(): void
    {
        $account = (new AccountFactory())->create(EstPos::class, 'test_bank', [
            'payment_model' => PosInterface::MODEL_NON_SECURE,
            'merchant_id'   => '700655000200',
            'user_name'     => 'ISBANKAPI',
            'user_password' => 'ISBANK07',
        ], PosInterface::LANG_EN);

        $this->assertSame(PosInterface::LANG_EN, $account->getLang());
    }

    public function test_default_lang_is_turkish(): void
    {
        $account = (new AccountFactory())->create(EstPos::class, 'test_bank', [
            'payment_model' => PosInterface::MODEL_NON_SECURE,
            'merchant_id'   => '700655000200',
            'user_name'     => 'ISBANKAPI',
            'user_password' => 'ISBANK07',
        ]);

        $this->assertSame(PosInterface::LANG_TR, $account->getLang());
    }
}
