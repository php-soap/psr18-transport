<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\HttpBinding;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Psr18Transport\HttpBinding\Psr7Converter;

class Psr7ConverterTest extends TestCase
{
    private Psr7Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new Psr7Converter(
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }

    function test_it_can_create_a_request()
    {
        $soapRequest = new SoapRequest('request', '/url', 'action', 1, 0);

        $request = $this->converter->convertSoapRequest($soapRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('request', $request->getBody()->__toString());
    }

    function test_it_can_create_a_response()
    {
        $stream = Psr17FactoryDiscovery::findStreamFactory()->createStream('response');
        $response = Psr17FactoryDiscovery::findResponseFactory()->createResponse()
            ->withBody($stream);

        $result = $this->converter->convertSoapResponse($response);
        self::assertInstanceOf(SoapResponse::class, $result);
        self::assertSame('response', $result->getPayload());
    }
}