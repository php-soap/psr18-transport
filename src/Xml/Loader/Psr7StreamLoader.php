<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Xml\Loader;

use DOMDocument;
use Psr\Http\Message\StreamInterface;
use VeeWee\Xml\Dom\Loader\Loader;
use function VeeWee\Xml\Dom\Configurator\loader;
use function VeeWee\Xml\Reader\Loader\xml_string_loader;

final class Psr7StreamLoader implements Loader
{
    private StreamInterface $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function __invoke(DOMDocument $document): void
    {
        loader(xml_string_loader(
            (string)$this->stream
        ))($document);
    }
}
