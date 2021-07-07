<?php

declare(strict_types=1);

namespace SoapTest\Psr18Transport\Unit\Middleware\WSICompliance;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Mock\Client;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Middleware\WSICompliance\QuotedSoapActionMiddleware;

final class QuotedSoapActionMiddlewareTest extends TestCase
{
    private PluginClient $client;
    private Client $mockClient;
    private QuotedSoapActionMiddleware $middleware;

    /*
     * Initialize all basic objects
     */
    protected function setUp(): void
    {
        $this->middleware = new QuotedSoapActionMiddleware();
        $this->mockClient = new Client();
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }

    
    public function test_it_is_a_middleware()
    {
        static::assertInstanceOf(Plugin::class, $this->middleware);
    }

    
    public function test_it_wraps_the_action_with_quotes()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => 'action']));

        $sentRequest = $this->mockClient->getRequests()[0];
        static::assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }

    
    public function test_it_keeps_the_action_quoted()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => '"action"']));

        $sentRequest = $this->mockClient->getRequests()[0];
        static::assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }

    
    public function test_it_transforms_single_quotes()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => "'action'"]));

        $sentRequest = $this->mockClient->getRequests()[0];
        static::assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }
}
