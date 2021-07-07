<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\HttpBinding;

use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Exception\RequestException;
use Soap\Psr18Transport\HttpBinding\Psr7RequestBuilder;

final class Psr7RequestBuilderTest extends TestCase
{
    private Psr7RequestBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new Psr7RequestBuilder(
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );
    }

    public function test_it_can_create_soap11_requests()
    {
        $this->builder->isSOAP11();
        $this->builder->setHttpMethod('POST');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $result = $this->builder->getHttpRequest();

        static::assertSame('POST', $result->getMethod());
        static::assertSame('text/xml; charset="utf-8"', $result->getHeaderLine('Content-Type'));
        static::assertSame((string) strlen($content), $result->getHeaderLine('Content-Length'));
        static::assertSame($action, $result->getHeaderLine('SOAPAction'));
        static::assertSame($endpoint, $result->getUri()->__toString());
    }

    public function test_it_can_not_use__ge_t_method_with_soap11()
    {
        $this->builder->isSOAP11();
        $this->builder->setHttpMethod('GET');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }

    public function test_it_can_create_soap12_requests()
    {
        $this->builder->isSOAP12();
        $this->builder->setHttpMethod('POST');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $result = $this->builder->getHttpRequest();

        static::assertSame('POST', $result->getMethod());
        static::assertSame(
            'application/soap+xml; charset="utf-8"; action="http://www.soapaction.com"',
            $result->getHeaderLine('Content-Type')
        );
        static::assertSame((string) strlen($content), $result->getHeaderLine('Content-Length'));
        static::assertSame(false, $result->hasHeader('SOAPAction'));
        static::assertSame($endpoint, $result->getUri()->__toString());
    }

    public function test_it_can_use__ge_t_method_with_soap12()
    {
        $this->builder->isSOAP12();
        $this->builder->setHttpMethod('GET');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $result = $this->builder->getHttpRequest();

        static::assertSame('GET', $result->getMethod());
        static::assertSame(false, $result->hasHeader('Content-Type'));
        static::assertSame(false, $result->hasHeader('Content-Length'));
        static::assertSame(false, $result->hasHeader('SOAPAction'));
        static::assertSame('application/soap+xml', $result->getHeaderLine('Accept'));
        static::assertSame($endpoint, $result->getUri()->__toString());
        static::assertSame('', $result->getBody()->__toString());
    }

    public function test_it_can_not_use__pu_t_method_with_soap12()
    {
        $this->builder->isSOAP12();
        $this->builder->setHttpMethod('PUT');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }

    public function test_it_needs_an_endpoint()
    {
        $this->builder->setSoapMessage('content');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }

    public function test_it_needs_a_message()
    {
        $this->builder->setEndpoint('http://www.endpoint.com');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }
}
