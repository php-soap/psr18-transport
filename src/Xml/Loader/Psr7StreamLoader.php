<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Xml\Loader;

use DOMDocument;
use Psr\Http\Message\StreamInterface;
use Soap\Psr18Transport\Exception\RequestException;
use VeeWee\Xml\Dom\Loader\Loader;
use function VeeWee\Xml\Dom\Loader\xml_string_loader;

final class Psr7StreamLoader implements Loader
{
    private StreamInterface $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @throws RequestException
     */
    public function __invoke(DOMDocument $document): void
    {
        $this->stream->rewind();
        $contents = (string) $this->stream;
        if (!$contents) {
            throw RequestException::noMessage();
        }

        xml_string_loader($contents)($document);
    }
}
