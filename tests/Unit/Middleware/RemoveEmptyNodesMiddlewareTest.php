<?php

namespace SoapTest\Psr18Transport\Unit\Middleware;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Middleware\RemoveEmptyNodesMiddleware;
use Soap\Xml\Xpath\EnvelopePreset;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Dom\Xpath;

class RemoveEmptyNodesMiddlewareTest extends TestCase
{
    private PluginClient$client;
    private Client $mockClient;
    private RemoveEmptyNodesMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new RemoveEmptyNodesMiddleware();
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
    function it_removes_empty_nodes_from_request_xml()
    {
        $soapRequest = file_get_contents(FIXTURE_DIR . '/soap/with-empty-nodes-request.xml');
        $this->mockClient->addResponse($response = new Response(200));
        $this->client->sendRequest($request = new Request('POST', '/', ['SOAPAction' => 'myaction'], $soapRequest));

        $soapBody = (string)$this->mockClient->getRequests()[0]->getBody();
        $xpath = $this->fetchEnvelopeXpath($soapBody);

        $this->assertEquals($xpath->query('//env:Body/ns1:UpdateCustomers/*')->count(), 3, 'Not all empty nodes are removed');
        $this->assertEquals($xpath->query('//env:Body/ns1:UpdateCustomers/ns1:UserID')->count(), 1, 'Not empty node is removed');
        $this->assertEquals($xpath->query('//env:Body/ns1:UpdateCustomers/ns1:CustomerID')->count(), 0, 'Empty node is removed');
        $this->assertEquals($xpath->query('//env:Body/ns1:UpdateCustomers/ns1:Customer/ns1:MailAddress')->count(), 1, 'Not empty parent node is removed');
        $this->assertEquals($xpath->query('//env:Body/ns1:UpdateCustomers/ns1:Customer/ns1:MailAddress/*')->count(), 3, 'Not all empty child nodes removed');
        $this->assertEquals($xpath->query('//env:Body/ns1:UpdateCustomers/ns1:Customer/ns1:MailAddress/ns1:AddressID')->count(), 0, 'Empty child node is removed');
    }


    private function fetchEnvelopeXpath($soapBody): Xpath
    {
        $document = Document::fromXmlString($soapBody);

        return $document->xpath(new EnvelopePreset($document));
    }
}
