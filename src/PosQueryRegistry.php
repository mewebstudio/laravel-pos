<?php

namespace Mews\LaravelPos;

use Mews\LaravelPos\Factory\GatewayFactory;
use Mews\LaravelPos\Factory\PosQueryFactory;
use Mews\Pos\PosQuery\PosQueryInterface;

/**
 * @phpstan-import-type BankConfig from GatewayFactory
 */
class PosQueryRegistry
{
    /** @var array<non-empty-string, PosQueryInterface> */
    private array $resolved = [];

    /** @phpstan-var array<non-empty-string, BankConfig> */
    private array $banks;
    private PosQueryFactory $posQueryFactory;

    /**
     * @phpstan-param array<non-empty-string, BankConfig> $banks
     */
    public function __construct(array $banks, PosQueryFactory $posQueryFactory)
    {
        $this->banks = $banks;
        $this->posQueryFactory = $posQueryFactory;
    }

    public function query(string $bankKey): PosQueryInterface
    {
        if ('' === $bankKey || !isset($this->banks[$bankKey])) {
            throw new \InvalidArgumentException(
                sprintf('No query registered for bank key "%s".', $bankKey)
            );
        }

        if (!isset($this->resolved[$bankKey])) {
            $this->resolved[$bankKey] = $this->posQueryFactory->create($bankKey, $this->banks[$bankKey]);
        }

        return $this->resolved[$bankKey];
    }

    /**
     * @return PosQueryInterface[]
     */
    public function all(): array
    {
        return array_map(
            fn (string $key) => $this->query($key),
            array_keys($this->banks)
        );
    }
}
