<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\Xml;

use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use VeeWee\Xml\Dom\Document;

class XmlMessageManipulatorTest extends TestCase
{
    /** @test */
    public function it_can_manipulate_an_xml_stream(): void
    {
        $xml = '<hello />';
        $stream = Psr17FactoryDiscovery::findStreamFactory()->createStream($xml);
        $message = Psr17FactoryDiscovery::findResponseFactory()->createResponse()->withBody($stream);

        $manipulated = (new XmlMessageManipulator)($message, function (Document $doc): void {
            $doc->manipulate(function (\DOMDocument $dom) {
                $dom->documentElement->setAttribute('name', 'world');
            });
        });

        self::assertInstanceOf(ResponseInterface::class, $manipulated);
        self::assertXmlStringEqualsXmlString('<hello name="world" />', (string)$manipulated->getBody());
    }
}
