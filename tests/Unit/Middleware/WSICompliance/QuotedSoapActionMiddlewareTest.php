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

class QuotedSoapActionMiddlewareTest extends TestCase
{
    private PluginClient $client;
    private Client $mockClient;
    private QuotedSoapActionMiddleware $middleware;

    /***
     * Initialize all basic objects
     */
    protected function setUp(): void
    {
        $this->middleware = new QuotedSoapActionMiddleware();
        $this->mockClient = new Client();
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }

    /**
     * @test
     */
    public function it_is_a_middleware()
    {
        $this->assertInstanceOf(Plugin::class, $this->middleware);
    }

    /**
     * @test
     */
    public function it_wraps_the_action_with_quotes()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => 'action']));

        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }

    /**
     * @test
     */
    public function it_keeps_the_action_quoted()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => '"action"']));

        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }

    /**
     * @test
     */
    public function it_transforms_single_quotes()
    {
        $this->mockClient->addResponse(new Response());
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => "'action'"]));

        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertSame('"action"', $sentRequest->getHeader('SOAPAction')[0]);
    }
}
