<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Xml\Mapper;

use DOMDocument;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\StreamInterface;
use VeeWee\Xml\Dom\Mapper\Mapper;

/**
 * @implements Mapper<StreamInterface>
 */
final class Psr7StreamMapper implements Mapper
{
    public function __invoke(DOMDocument $document): StreamInterface
    {
        $factory = Psr17FactoryDiscovery::findStreamFactory();
        $stream = $factory->createStream($document->saveXML());
        $stream->rewind();

        return $stream;
    }
}
