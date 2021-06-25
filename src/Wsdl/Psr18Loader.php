<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Wsdl;


use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class Psr18Loader implements WsdlLoader
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
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
