<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\HttpBinding;

use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Exception\RequestException;
use Soap\Psr18Transport\HttpBinding\Psr7RequestBuilder;

class Psr7RequestBuilderTest extends TestCase
{
    private Psr7RequestBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new Psr7RequestBuilder(
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );
    }

    function test_it_can_create_soap11_requests()
    {
        $this->builder->isSOAP11();
        $this->builder->setHttpMethod('POST');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $result = $this->builder->getHttpRequest();

        self::assertSame('POST', $result->getMethod());
        self::assertSame('text/xml; charset="utf-8"', $result->getHeaderLine('Content-Type'));
        self::assertSame((string) strlen($content), $result->getHeaderLine('Content-Length'));
        self::assertSame($action, $result->getHeaderLine('SOAPAction'));
        self::assertSame($endpoint, $result->getUri()->__toString());
    }

    function test_it_can_not_use_GET_method_with_soap11()
    {
        $this->builder->isSOAP11();
        $this->builder->setHttpMethod('GET');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }

    function test_it_can_create_soap12_requests()
    {
        $this->builder->isSOAP12();
        $this->builder->setHttpMethod('POST');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $result = $this->builder->getHttpRequest();

        self::assertSame('POST', $result->getMethod());
        self::assertSame(
            'application/soap+xml; charset="utf-8"; action="http://www.soapaction.com"',
            $result->getHeaderLine('Content-Type')
        );
        self::assertSame((string) strlen($content), $result->getHeaderLine('Content-Length'));
        self::assertSame(false, $result->hasHeader('SOAPAction'));
        self::assertSame($endpoint, $result->getUri()->__toString());
    }

    function test_it_can_use_GET_method_with_soap12()
    {
        $this->builder->isSOAP12();
        $this->builder->setHttpMethod('GET');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $result = $this->builder->getHttpRequest();

        self::assertSame('GET', $result->getMethod());
        self::assertSame(false, $result->hasHeader('Content-Type'));
        self::assertSame(false, $result->hasHeader('Content-Length'));
        self::assertSame(false, $result->hasHeader('SOAPAction'));
        self::assertSame('application/soap+xml', $result->getHeaderLine('Accept'));
        self::assertSame($endpoint, $result->getUri()->__toString());
        self::assertSame('', $result->getBody()->__toString());
    }

    function test_it_can_not_use_PUT_method_with_soap12()
    {
        $this->builder->isSOAP12();
        $this->builder->setHttpMethod('PUT');
        $this->builder->setEndpoint($endpoint = 'http://www.endpoint.com');
        $this->builder->setSoapAction($action = 'http://www.soapaction.com');
        $this->builder->setSoapMessage($content = 'content');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }

    function test_it_needs_an_endpoint()
    {
        $this->builder->setSoapMessage('content');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }

    function test_it_needs_a_message()
    {
        $this->builder->setEndpoint('http://www.endpoint.com');

        $this->expectException(RequestException::class);
        $this->builder->getHttpRequest();
    }
}
