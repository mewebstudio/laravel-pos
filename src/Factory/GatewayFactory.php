<?php

namespace Mews\LaravelPos\Factory;

use Mews\Pos\Factory\CryptFactory;
use Mews\Pos\Factory\HttpClientFactory;
use Mews\Pos\Factory\RequestDataMapperFactory;
use Mews\Pos\Factory\ResponseDataMapperFactory;
use Mews\Pos\Factory\SerializerFactory;
use Mews\Pos\PosInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

/** @internal */
class GatewayFactory
{
    private AccountFactoryInterface $accountFactory;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private ClientInterface $client;

    public function __construct(
        AccountFactoryInterface  $accountFactory,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface          $logger,
        ClientInterface          $client
    ) {
        $this->accountFactory  = $accountFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger          = $logger;
        $this->client          = $client;
    }

    public function create(string $name, array $options): PosInterface
    {
        $credentials  = $options['credentials'];
        $gatewayClass = $options['gateway_class'];
        if (!\in_array(PosInterface::class, \class_implements($gatewayClass), true)) {
            throw new \InvalidArgumentException(
                \sprintf('gateway_class must be implementation of %s', PosInterface::class)
            );
        }

        $account            = $this->accountFactory->create(
            $gatewayClass,
            $name,
            $credentials,
            $options['lang'] ?? PosInterface::LANG_TR
        );
        $crypt              = CryptFactory::createGatewayCrypt($gatewayClass, $this->logger);
        $requestDataMapper  = RequestDataMapperFactory::createGatewayRequestMapper(
            $gatewayClass,
            $this->eventDispatcher,
            $crypt
        );
        $responseDataMapper = ResponseDataMapperFactory::createGatewayResponseMapper(
            $gatewayClass,
            $requestDataMapper,
            $this->logger
        );
        $serializer         = SerializerFactory::createGatewaySerializer($gatewayClass);

        /** @var PosInterface $gateway */
        $gateway = new $gatewayClass(
            [
                'gateway_endpoints' => $options['gateway_endpoints'],
                'gateway_configs'   => $options['gateway_configs'] ?? [],
            ],
            $account,
            $requestDataMapper,
            $responseDataMapper,
            $serializer,
            $this->eventDispatcher,
            HttpClientFactory::createHttpClient($this->client),
            $this->logger,
        );

        // todo remove this in next major version
        if (!isset($options['gateway_configs']['test_mode']) && isset($options['test_mode'])) {
            $gateway->setTestMode($options['test_mode']);
        }

        return $gateway;
    }
}
