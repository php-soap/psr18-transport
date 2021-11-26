<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\HttpBinding;

use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;
use Soap\Psr18Transport\HttpBinding\Psr7Converter;

final class Psr7ConverterTest extends TestCase
{
    private Psr7Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new Psr7Converter(
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory()
        );
    }

    public function test_it_can_create_a_request()
    {
        $soapRequest = new SoapRequest('request', '/url', 'action', 1, false);

        $request = $this->converter->convertSoapRequest($soapRequest);

        static::assertInstanceOf(RequestInterface::class, $request);
        static::assertSame('request', $request->getBody()->__toString());
    }

    public function test_it_can_create_a_response()
    {
        $stream = Psr17FactoryDiscovery::findStreamFactory()->createStream('response');
        $response = Psr17FactoryDiscovery::findResponseFactory()->createResponse()
            ->withBody($stream);

        $result = $this->converter->convertSoapResponse($response);
        static::assertInstanceOf(SoapResponse::class, $result);
        static::assertSame('response', $result->getPayload());
    }
}
