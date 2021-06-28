<?php


namespace Mews\LaravelPos\Factory;


use Mews\Pos\Entity\Account\EstPosAccount;
use Mews\Pos\Entity\Account\GarantiPosAccount;
use Mews\Pos\Entity\Account\PayForAccount;
use Mews\Pos\Entity\Account\PosNetAccount;
use Mews\Pos\Entity\Account\VakifBankAccount;
use Mews\Pos\Exceptions\BankNotFoundException;

class AccountFactory
{
    const ACCOUNTS = [
        'akbank'               => EstPosAccount::class,
        'ziraat'               => EstPosAccount::class,
        'isbank'               => EstPosAccount::class,
        'finansbank'           => EstPosAccount::class,
        'halkbank'             => EstPosAccount::class,
        'teb'                  => EstPosAccount::class,
        'yapikredi'            => PosNetAccount::class,
        'garanti'              => GarantiPosAccount::class,
        'qnbfinansbank-payfor' => PayForAccount::class,
        'vakifbank'            => VakifBankAccount::class,
    ];

    public static function create(array $account)
    {
        $class = self::ACCOUNTS[$account['bank']] ?? null;

        if (! $class) {
            throw new BankNotFoundException();
        }

        $params = [
            $account['bank'],
            $account['model'],
            $account['client_id'],
        ];

        if ($account['bank'] != 'vakifbank') {
            $params[] = $account['username'];
        }

        $params[] = $account['password'];

        if ($account['bank'] != 'vakifbank') {
            $params[] = $account['lang'] ?? 'tr';
        }

        switch ($account['bank']) {
            case "garanti":
                $params[] = $account['terminal_id'];
                $params[] = $account['store_key'] ?? null;
                $params[] = $account['refund_username'] ?? null;
                $params[] = $account['refund_password'] ?? null;
                break;
            case "yapikredi":
                $params[] = $account['terminal_id'];
                $params[] = $account['pos_net_id'];
                $params[] = $account['store_key'];
                break;
            case "vakifbank":
                $params[] = $account['terminal_id'];
                $params[] = $account['merchant_type'] ?? 0;
                $params[] = $account['sub_merchant_id'] ?? null;
        }

        if ($account['bank'] != 'vakifbank') {
            $params[] = $account['store_key'] ?? null;
        }

        return new $class(...$params);
    }
}