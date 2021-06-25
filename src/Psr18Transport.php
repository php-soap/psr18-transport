<?php

declare(strict_types=1);

namespace Soap\Psr18Transport;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Engine\Transport;
use Soap\Psr18Transport\HttpBinding\Psr7Converter;

final class Psr18Transport implements Transport
{
    private ClientInterface $client;
    private Psr7Converter $converter;

    public function __construct(
        ClientInterface $client,
        Psr7Converter $converter
    ) {
        $this->client = $client;
        $this->converter = $converter;
    }

    public static function createWithDefaultClient(): self
    {
        return self::createForClient(Psr18ClientDiscovery::find());
    }

    public static function createForClient(ClientInterface $client): self
    {
        return new self(
            $client,
            new Psr7Converter(
                Psr17FactoryDiscovery::findRequestFactory(),
                Psr17FactoryDiscovery::findStreamFactory()
            )
        );
    }

    public function request(SoapRequest $request): SoapResponse
    {
        $psr7Request = $this->converter->convertSoapRequest($request);
        $psr7Response = $this->client->sendRequest($psr7Request);

        return $this->converter->convertSoapResponse($psr7Response);
    }
}
