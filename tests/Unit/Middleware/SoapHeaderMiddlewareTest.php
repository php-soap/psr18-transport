<?php declare(strict_types=1);

namespace SoapTest\Psr18Transport\Unit\Middleware;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soap\Psr18Transport\Middleware\SoapHeaderMiddleware;
use Soap\Xml\Builder\Header\Actor;
use Soap\Xml\Builder\Header\MustUnderstand;
use Soap\Xml\Builder\SoapHeader;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Builder\children;
use function VeeWee\Xml\Dom\Builder\namespaced_element;
use function VeeWee\Xml\Dom\Builder\value;
use function VeeWee\Xml\Dom\Configurator\comparable;

final class SoapHeaderMiddlewareTest extends TestCase
{
    private PluginClient $client;
    private Client $mockClient;
    private SoapHeaderMiddleware $middleware;

    protected function setUp(): void
    {
        $tns = 'https://foo.bar';
        $this->middleware = new SoapHeaderMiddleware(
            new SoapHeader(
                $tns,
                'x:Auth',
                children(
                    namespaced_element($tns, 'x:user', value('josbos')),
                    namespaced_element($tns, 'x:password', value('topsecret'))
                )
            ),
            new SoapHeader($tns, 'Acting', Actor::next()),
            new SoapHeader($tns, 'Understanding', new MustUnderstand())
        );
        $this->mockClient = new Client(Psr17FactoryDiscovery::findResponseFactory());
        $this->client = new PluginClient($this->mockClient, [$this->middleware]);
    }

    
    public function test_it_is_a_middleware()
    {
        static::assertInstanceOf(Plugin::class, $this->middleware);
    }

    public function test_it_appends_soap_headers()
    {
        $soapRequest = file_get_contents(FIXTURE_DIR . '/soap/empty-envelope.xml');
        $this->mockClient->addResponse($response = new Response(200));
        $this->client->sendRequest($request = new Request('POST', '/', ['SOAPAction' => 'myaction'], $soapRequest));

        $soapBody = (string)$this->mockClient->getRequests()[0]->getBody();
        $doc = Document::fromXmlString($soapBody, comparable());

        $expected = <<<EOXML
        <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope/"
                       soap:encodingStyle="http://www.w3.org/2003/05/soap-encoding">
            <soap:Header xmlns:soap="http://www.w3.org/2003/05/soap-envelope/">
                <x:Auth xmlns:x="https://foo.bar">
                    <x:user>josbos</x:user>
                    <x:password>topsecret</x:password>
                </x:Auth>
                <Acting xmlns="https://foo.bar" soap:actor="http://schemas.xmlsoap.org/soap/actor/next" />
                <Understanding xmlns="https://foo.bar" soap:mustUnderstand="1" />
            </soap:Header>
        </soap:Envelope>
        EOXML;

        static::assertXmlStringEqualsXmlString(
            Document::fromXmlString($expected, comparable())->toXmlString(),
            Document::fromUnsafeDocument($doc->toUnsafeDocument(), comparable())->toXmlString()
        );
    }
}
