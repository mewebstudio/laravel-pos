<?php

namespace Mews\LaravelPos\Factory;

use Mews\Pos\Factory\AccountFactory as MewsPosAccountFactory;
use Mews\Pos\Factory\PosQueryFactory as MewsPosPosQueryFactory;
use Mews\Pos\PosQuery\PosQueryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-import-type BankConfig from GatewayFactory
 *
 * @internal
 */
class PosQueryFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface          $logger,
        private ClientInterface          $client,
    ) {
    }

    /**
     * @phpstan-param BankConfig $options
     */
    public function create(string $name, array $options): PosQueryInterface
    {
        if ('' === $name) {
            throw new \InvalidArgumentException('Bank key must not be empty.');
        }

        $account = MewsPosAccountFactory::createForGateway(
            $options['gateway_class'],
            $name,
            $options['credentials']
        );

        $config = [
            'class' => $options['gateway_class'],
            'gateway_endpoints' => $options['gateway_endpoints'],
            'gateway_configs' => $options['gateway_configs'] ?? [],
        ];

        return MewsPosPosQueryFactory::create($account, $config, $this->eventDispatcher, $this->client, $this->logger);
    }
}
