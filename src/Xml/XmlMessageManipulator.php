<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Xml;

use Psr\Http\Message\MessageInterface;
use Soap\Psr18Transport\Xml\Loader\Psr7StreamLoader;
use Soap\Psr18Transport\Xml\Mapper\Psr7StreamMapper;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Configurator\loader;

final class XmlMessageManipulator
{
    /**
     * @template T of MessageInterface
     * @param T $message
     * @param callable(Document): void $manipulator
     * @return T
     */
    public function __invoke(MessageInterface $message, callable $manipulator): MessageInterface
    {
        $document = Document::configure(loader(new Psr7StreamLoader($message->getBody())));
        $manipulator($document);

        return $message->withBody($document->map(new Psr7StreamMapper()));
    }
}
