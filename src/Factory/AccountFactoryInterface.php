<?php

namespace Mews\LaravelPos\Factory;

use Mews\Pos\Entity\Account\AbstractPosAccount;
use Mews\Pos\Exceptions\MissingAccountInfoException;
use Mews\Pos\PosInterface;

interface AccountFactoryInterface
{
    /**
     * @throws MissingAccountInfoException
     * @throws \DomainException
     */
    public function create(
        string $gatewayClass,
        string $name,
        array  $credentials,
        string $lang = PosInterface::LANG_TR
    ): AbstractPosAccount;
}
