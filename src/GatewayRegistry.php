<?php

namespace Mews\LaravelPos;

use Mews\LaravelPos\Factory\AccountFactoryInterface;
use Mews\LaravelPos\Factory\GatewayFactory;
use Mews\Pos\PosInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class GatewayRegistry
{
    /** @var PosInterface[] */
    private array $resolved = [];

    private array $banks;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private ClientInterface $httpClient;
    private AccountFactoryInterface $accountFactory;

    public function __construct(
        array $banks,
        AccountFactoryInterface $accountFactory,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        ClientInterface $httpClient
    ) {
        $this->banks           = $banks;
        $this->accountFactory  = $accountFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger          = $logger;
        $this->httpClient      = $httpClient;
    }

    public function gateway(string $bankKey): PosInterface
    {
        if (!isset($this->banks[$bankKey])) {
            throw new \InvalidArgumentException(
                sprintf('No gateway registered for bank key "%s".', $bankKey)
            );
        }

        if (!isset($this->resolved[$bankKey])) {
            $this->resolved[$bankKey] = GatewayFactory::create(
                $bankKey,
                $this->banks[$bankKey],
                $this->accountFactory,
                $this->eventDispatcher,
                $this->logger,
                $this->httpClient,
            );
        }

        return $this->resolved[$bankKey];
    }

    /**
     * @return PosInterface[]
     */
    public function all(): array
    {
        return array_map(
            fn(string $key) => $this->gateway($key),
            array_keys($this->banks)
        );
    }
}
