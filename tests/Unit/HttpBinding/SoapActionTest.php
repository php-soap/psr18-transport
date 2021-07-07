<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\HttpBinding;

use Http\Client\Exception\RequestException;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\HttpBinding\SoapActionDetector;

final class SoapActionTest extends TestCase
{
    
    public function test_it_can_detect_soap_action_from_soap_11__soap_action_header()
    {
        $request = $this->createRequest()->withAddedHeader('SoapAction', 'actionhere');
        $result = (new SoapActionDetector())->detectFromRequest($request);

        static::assertSame('actionhere', $result);
    }

    
    public function test_it_can_detect_soap_action_from_soap_12_content_type_header_with_double_quote()
    {
        $request = $this->createRequest()
            ->withAddedHeader('Content-Type', 'application/soap+xml;charset=UTF-8;action="actionhere"');

        $result = (new SoapActionDetector())->detectFromRequest($request);

        static::assertSame('actionhere', $result);
    }

    
    public function test_it_can_detect_soap_action_from_soap_12_content_type_header_with_single_quote()
    {
        $request = $this->createRequest()
            ->withAddedHeader('Content-Type', 'application/soap+xml;charset=UTF-8;action=\'actionhere\'');
        $result = (new SoapActionDetector())->detectFromRequest($request);

        static::assertSame('actionhere', $result);
    }

    
    public function test_it_throws_an_http_request_exception_when_no_header_could_be_found()
    {
        $this->expectException(RequestException::class);

        $request = $this->createRequest();
        (new SoapActionDetector())->detectFromRequest($request);
    }

    private function createRequest(): RequestInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory()->createRequest('GET', '/soap');
    }
}
