<?php

namespace SoapTest\Psr18Transport\Unit\Middleware\Wsdl;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Middleware\Wsdl\DisablePoliciesMiddleware;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;

class DisablePoliciesMiddlewareTest extends TestCase
{
    private PluginClient $client;
    private Client $mockClient;
    private DisablePoliciesMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new DisablePoliciesMiddleware();
        $this->mockClient = new Client(Psr17FactoryDiscovery::findResponseFactory());
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }

    /**
     * @test
     */
    function it_is_a_middleware()
    {
        $this->assertInstanceOf(Plugin::class, $this->middleware);
    }

    /**
     * @test
     */
    function it_removes_wsdl_policies()
    {
        $this->mockClient->addResponse(new Response(
            200,
            [],
            file_get_contents(FIXTURE_DIR . '/wsdl/wsdl-policies.wsdl'))
        );

        $response = $this->client->sendRequest(new Request('POST', '/'));
        $doc = Document::fromXmlString((string) $response->getBody());
        $xpath = $doc->xpath(
            namespaces([
                'wsd' => 'http://schemas.xmlsoap.org/ws/2004/09/policy'
            ])
        );

        $this->assertEquals(0, $xpath->query('//wsd:Policy')->count(), 'Still got policies in WSDL file.');
        $this->assertEquals(0, $xpath->query('//wsd:UsingPolicy')->count(), 'Still got using statements for policies in WSDL file.');
    }
}
