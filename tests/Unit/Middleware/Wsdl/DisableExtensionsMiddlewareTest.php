<?php declare(strict_types=1);

namespace SoapTest\Psr18Transport\Unit\Middleware\Wsdl;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Middleware\Wsdl\DisableExtensionsMiddleware;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Document;

final class DisableExtensionsMiddlewareTest extends TestCase
{
    private PluginClient $client;
    private Client $mockClient;
    private DisableExtensionsMiddleware $middleware;

    /*
     * Initialize all basic objects
     */
    protected function setUp(): void
    {
        $this->middleware = new DisableExtensionsMiddleware();
        $this->mockClient = new Client(Psr17FactoryDiscovery::findResponseFactory());
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }

    
    public function test_it_is_a_middleware()
    {
        static::assertInstanceOf(Plugin::class, $this->middleware);
    }

    
    public function test_it_removes_required_wsdl_extensions()
    {
        $this->mockClient->addResponse(
            new Response(
            200,
            [],
            file_get_contents(FIXTURE_DIR . '/wsdl/wsdl-extensions.wsdl')
        )
        );

        $response = $this->client->sendRequest(new Request('POST', '/'));

        $doc = Document::fromXmlString((string) $response->getBody());
        $xpath = $doc->xpath(new WsdlPreset($doc));
        $expression = '//wsdl:binding/wsaw:UsingAddressing[@wsdl:required="%s"]';

        static::assertEquals(0, $xpath->query(sprintf($expression, 'true'))->count(), 'Still got required WSDL extensions.');
        static::assertEquals(1, $xpath->query(sprintf($expression, 'false'))->count(), 'Cannot find any deactivated WSDL extensions.');
    }
}
