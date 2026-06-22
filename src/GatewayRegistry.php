<?php

namespace Mews\LaravelPos;

use Illuminate\Contracts\Container\Container;
use Mews\Pos\PosInterface;

class GatewayRegistry
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function gateway(string $bankKey): PosInterface
    {
        $id = "laravel-pos:gateway:$bankKey";
        if (!$this->container->bound($id)) {
            throw new \InvalidArgumentException(
                sprintf('No gateway registered for bank key "%s".', $bankKey)
            );
        }

        return $this->container->make($id);
    }

    /**
     * @return PosInterface[]
     */
    public function all(): array
    {
        return iterator_to_array($this->container->tagged('laravel-pos:gateway'));
    }
}
