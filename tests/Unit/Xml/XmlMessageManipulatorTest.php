<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\Xml;

use DOMDocument;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use VeeWee\Xml\Dom\Document;

final class XmlMessageManipulatorTest extends TestCase
{
    public function test_it_can_manipulate_an_xml_stream(): void
    {
        $xml = '<hello />';
        $stream = Psr17FactoryDiscovery::findStreamFactory()->createStream($xml);
        $message = Psr17FactoryDiscovery::findResponseFactory()->createResponse()->withBody($stream);

        $manipulated = (new XmlMessageManipulator)($message, static function (Document $doc): void {
            $doc->manipulate(static function (DOMDocument $dom) {
                $dom->documentElement->setAttribute('name', 'world');
            });
        });

        static::assertInstanceOf(ResponseInterface::class, $manipulated);
        static::assertXmlStringEqualsXmlString('<hello name="world" />', (string)$manipulated->getBody());
    }
}
