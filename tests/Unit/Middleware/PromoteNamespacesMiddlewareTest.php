<?php declare(strict_types=1);

namespace SoapTest\Psr18Transport\Unit\Middleware;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Middleware\PromoteNamespacesMiddleware;
use Soap\Xml\Xpath\EnvelopePreset;
use VeeWee\Xml\Dom\Document;
use VeeWee\Xml\Dom\Xpath;
use function VeeWee\Xml\Dom\Locator\document_element;

final class PromoteNamespacesMiddlewareTest extends TestCase
{
    private const XMLNS = 'http://www.w3.org/2000/xmlns/';

    private PluginClient $client;
    private Client $mockClient;
    private PromoteNamespacesMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new PromoteNamespacesMiddleware();
        $this->mockClient = new Client(Psr17FactoryDiscovery::findResponseFactory());
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }

    public function test_it_is_a_middleware(): void
    {
        static::assertInstanceOf(Plugin::class, $this->middleware);
    }

    public function test_it_promotes_descendant_namespaces_to_envelope(): void
    {
        $soapRequest = file_get_contents(FIXTURE_DIR . '/soap/with-descendant-namespaces-request.xml');
        $this->mockClient->addResponse(new Response(200));
        $this->client->sendRequest(new Request('POST', '/', ['SOAPAction' => 'myaction'], $soapRequest));

        $soapBody = (string) $this->mockClient->getRequests()[0]->getBody();
        $document = Document::fromXmlString($soapBody);
        $envelope = $document->map(document_element());

        static::assertSame(
            'https://example.com',
            $envelope->getAttributeNS(self::XMLNS, 'tns'),
            'xmlns:tns must be declared on the envelope element itself'
        );
        static::assertSame(
            'http://www.w3.org/2001/XMLSchema-instance',
            $envelope->getAttributeNS(self::XMLNS, 'xsi'),
            'xmlns:xsi must be declared on the envelope element itself'
        );
        static::assertSame(
            'http://www.w3.org/2001/XMLSchema',
            $envelope->getAttributeNS(self::XMLNS, 'xsd'),
            'xmlns:xsd must be declared on the envelope element itself'
        );

        $xpath = $this->fetchEnvelopeXpath($document);
        static::assertSame(
            0,
            $xpath->query('//soap-env:Envelope/descendant::*/@*[namespace-uri()="' . self::XMLNS . '"]')->count(),
            'no descendant element must carry its own xmlns:* declaration'
        );

        static::assertSame(1, $xpath->query('//soap-env:Body/tns:GetUser/tns:Id')->count());
        static::assertSame('1', (string) $xpath->query('//soap-env:Body/tns:GetUser/tns:Id')->item(0)->textContent);
    }

    public function test_it_leaves_default_namespace_in_place(): void
    {
        $soapRequest = <<<'EOXML'
        <?xml version="1.0" encoding="UTF-8"?>
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
            <SOAP-ENV:Body>
                <Payload xmlns="https://example.com/default"><Inner/></Payload>
            </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>
        EOXML;

        $this->mockClient->addResponse(new Response(200));
        $this->client->sendRequest(new Request('POST', '/', [], $soapRequest));

        $document = Document::fromXmlString((string) $this->mockClient->getRequests()[0]->getBody());
        $envelope = $document->map(document_element());
        $payload = $envelope->getElementsByTagName('Payload')->item(0);

        static::assertNotNull($payload);
        static::assertSame('https://example.com/default', $payload->namespaceURI);
        static::assertTrue($payload->hasAttribute('xmlns'), 'default xmlns must remain on the Payload element');
        static::assertFalse($envelope->hasAttribute('xmlns'), 'default xmlns must not leak onto the envelope');
    }

    private function fetchEnvelopeXpath(Document $document): Xpath
    {
        return $document->xpath(
            new EnvelopePreset($document),
            \VeeWee\Xml\Dom\Xpath\Configurator\namespaces([
                'tns' => 'https://example.com',
                'soap-env' => 'http://schemas.xmlsoap.org/soap/envelope/',
            ])
        );
    }
}
