<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Wsdl;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Soap\Wsdl\Loader\WsdlLoader;

final class Psr18Loader implements WsdlLoader
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    public static function createForClient(ClientInterface $client): self
    {
        return new self(
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
        );
    }

    public function __invoke(string $location): string
    {
        $response = $this->client->sendRequest(
            $this->requestFactory->createRequest('GET', $location)
        );

        $body = $response->getBody();
        $body->rewind();

        return (string) $body;
    }
}
