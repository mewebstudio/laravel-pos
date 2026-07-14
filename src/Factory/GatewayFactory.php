<?php

namespace Mews\LaravelPos\Factory;

use Mews\Pos\Factory\AccountFactory as MewsPosAccountFactory;
use Mews\Pos\Factory\PosFactory;
use Mews\Pos\PosInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type BankConfig array{
 *     gateway_class: class-string<PosInterface>,
 *     credentials: array<non-empty-string, non-empty-string>,
 *     gateway_endpoints: array{
 *         payment_api: non-empty-string,
 *         gateway_3d?: non-empty-string,
 *         gateway_3d_host?: non-empty-string,
 *         query_api?: non-empty-string,
 *     },
 *     gateway_configs?: array{
 *         test_mode?: bool,
 *         lang?: PosInterface::LANG_*,
 *         disable_3d_hash_check?: bool,
 *     },
 * }
 *
 * @internal
 */
class GatewayFactory
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
    public function create(string $name, array $options): PosInterface
    {
        if ('' === $name) {
            throw new \InvalidArgumentException('Bank key must not be empty.');
        }

        if (!\is_a($options['gateway_class'], PosInterface::class, true)) {
            throw new \InvalidArgumentException(
                \sprintf('gateway_class must be an implementation of %s', PosInterface::class)
            );
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

        return PosFactory::create($account, $config, $this->eventDispatcher, null, $this->client, $this->logger);
    }
}
