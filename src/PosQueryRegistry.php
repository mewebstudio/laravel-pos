<?php

namespace Mews\LaravelPos;

use Mews\LaravelPos\Factory\PosQueryFactory;
use Mews\Pos\PosQuery\PosQueryInterface;

class PosQueryRegistry
{
    /** @var PosQueryInterface[] */
    private array $resolved = [];

    private array $banks;
    private PosQueryFactory $posQueryFactory;

    public function __construct(array $banks, PosQueryFactory $posQueryFactory)
    {
        $this->banks           = $banks;
        $this->posQueryFactory = $posQueryFactory;
    }

    public function query(string $bankKey): PosQueryInterface
    {
        if (!isset($this->banks[$bankKey])) {
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
            fn(string $key) => $this->query($key),
            array_keys($this->banks)
        );
    }
}
