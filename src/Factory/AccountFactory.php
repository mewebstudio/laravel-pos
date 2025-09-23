<?php

namespace Mews\LaravelPos\Factory;

use Mews\Pos\Entity\Account\AbstractPosAccount;
use Mews\Pos\Entity\Account\PayFlexAccount;
use Mews\Pos\Entity\Account\PayForAccount;
use Mews\Pos\Exceptions\MissingAccountInfoException;
use Mews\Pos\Factory\AccountFactory as MewsPosAccountFactory;
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

class AccountFactory
{
    /**
     * @param string $gatewayClass
     * @param string $name
     * @param array  $credentials
     * @param string $lang
     *
     * @return AbstractPosAccount
     *
     * @throws MissingAccountInfoException
     * @throws \DomainException
     */
    public static function create(string $gatewayClass, string $name, array $credentials, string $lang = PosInterface::LANG_TR): AbstractPosAccount
    {
        switch ($gatewayClass) {
            case EstPos::class:
            case EstV3Pos::class:
                return MewsPosAccountFactory::createEstPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['user_password'],
                    $credentials['payment_model'],
                    $credentials['enc_key'] ?? null,
                    $lang,
                );
            case PosNet::class:
            case PosNetV1Pos::class:
                return MewsPosAccountFactory::createPosNetAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['terminal_id'],
                    $credentials['user_name'],
                    $credentials['payment_model'],
                    $credentials['enc_key'] ?? null,
                    $lang,
                );
            case PayForPos::class:
                return MewsPosAccountFactory::createPayForAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['user_password'],
                    $credentials['payment_model'],
                    $credentials['enc_key'] ?? null,
                    $lang,
                    $credentials['mbr_id'] ?? PayForAccount::MBR_ID_FINANSBANK,
                );
            case GarantiPos::class:
                return MewsPosAccountFactory::createGarantiPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['user_password'],
                    $credentials['terminal_id'],
                    $credentials['payment_model'],
                    $credentials['enc_key'] ?? null,
                    $credentials['refund_user_name'] ?? null,
                    $credentials['refund_user_password'] ?? null,
                    $lang,
                );
            case InterPos::class:
                return MewsPosAccountFactory::createInterPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['user_password'],
                    $credentials['payment_model'],
                    $credentials['enc_key'] ?? null,
                    $lang,
                );
            case KuveytPos::class:
            case VakifKatilimPos::class:
                return MewsPosAccountFactory::createKuveytPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['terminal_id'],
                    $credentials['enc_key'],
                    $credentials['payment_model'],
                    $lang,
                    $credentials['sub_merchant_id'] ?? null,
                );
            case PayFlexV4Pos::class:
            case PayFlexCPV4Pos::class:
                return MewsPosAccountFactory::createPayFlexAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_password'],
                    $credentials['terminal_id'],
                    $credentials['payment_model'],
                    PayFlexAccount::MERCHANT_TYPE_STANDARD,
                    $credentials['sub_merchant_id'] ?? null,
                );
            case AkbankPos::class:
                return MewsPosAccountFactory::createAkbankPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['terminal_id'],
                    $credentials['enc_key'],
                    $lang,
                    $credentials['sub_merchant_id'] ?? null,
                );
            case ToslaPos::class:
                return MewsPosAccountFactory::createToslaPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['enc_key'],
                );
            case ParamPos::class:
                return MewsPosAccountFactory::createParamPosAccount(
                    $name,
                    $credentials['merchant_id'],
                    $credentials['user_name'],
                    $credentials['user_password'],
                    $credentials['enc_key'],
                );
        }

        throw new \DomainException(
            \sprintf('Can not create matching Account object for % gateway', $gatewayClass)
        );
    }
}
