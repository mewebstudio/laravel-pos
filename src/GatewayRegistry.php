<?php

namespace Mews\LaravelPos;

use Mews\LaravelPos\Factory\GatewayFactory;
use Mews\Pos\PosInterface;

/**
 * @phpstan-import-type BankConfig from GatewayFactory
 */
class GatewayRegistry
{
    /** @var array<non-empty-string, PosInterface> */
    private array $resolved = [];

    /** @phpstan-var array<non-empty-string, BankConfig> */
    private array $banks;
    private GatewayFactory $gatewayFactory;

    /**
     * @phpstan-param array<non-empty-string, BankConfig> $banks
     */
    public function __construct(array $banks, GatewayFactory $gatewayFactory)
    {
        $this->banks = $banks;
        $this->gatewayFactory = $gatewayFactory;
    }

    public function gateway(string $bankKey): PosInterface
    {
        if ('' === $bankKey || !isset($this->banks[$bankKey])) {
            throw new \InvalidArgumentException(
                sprintf('No gateway registered for bank key "%s".', $bankKey)
            );
        }

        if (!isset($this->resolved[$bankKey])) {
            $this->resolved[$bankKey] = $this->gatewayFactory->create($bankKey, $this->banks[$bankKey]);
        }

        return $this->resolved[$bankKey];
    }

    /**
     * @return PosInterface[]
     */
    public function all(): array
    {
        return array_map(
            fn (string $key) => $this->gateway($key),
            array_keys($this->banks)
        );
    }
}
