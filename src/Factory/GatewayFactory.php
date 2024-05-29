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

class GatewayFactory
{
    public static function create(
        string                   $name,
        array                    $options,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface          $logger,
        ClientInterface          $client
    ): PosInterface
    {
        $credentials  = $options['credentials'];
        $gatewayClass = $options['gateway_class'];
        if (!\in_array(PosInterface::class, \class_implements($gatewayClass), true)) {
            throw new \InvalidArgumentException(
                \sprintf('gateway_class must be implementation of %s', PosInterface::class)
            );
        }

        $account            = AccountFactory::create(
            $gatewayClass,
            $name,
            $credentials,
            $options['lang'] ?? PosInterface::LANG_TR
        );
        $crypt              = CryptFactory::createGatewayCrypt($gatewayClass, $logger);
        $requestDataMapper  = RequestDataMapperFactory::createGatewayRequestMapper(
            $gatewayClass,
            $eventDispatcher,
            $crypt
        );
        $responseDataMapper = ResponseDataMapperFactory::createGatewayResponseMapper(
            $gatewayClass,
            $requestDataMapper,
            $logger
        );
        $serializer         = SerializerFactory::createGatewaySerializer($gatewayClass);

        /** @var PosInterface $gateway */
        $gateway = new $gatewayClass(
            ['gateway_endpoints' => $options['gateway_endpoints']],
            $account,
            $requestDataMapper,
            $responseDataMapper,
            $serializer,
            $eventDispatcher,
            HttpClientFactory::createHttpClient($client),
            $logger,
        );

        $gateway->setTestMode($options['test_mode'] ?? false);

        return $gateway;
    }
}
