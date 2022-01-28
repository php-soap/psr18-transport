<?php
declare(strict_types=1);

namespace Soap\Psr18Transport\Middleware;

use DOMElement;
use DOMNode;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use Soap\Xml\Builder\SoapHeaders;
use Soap\Xml\Manipulator\PrependSoapHeaders;
use VeeWee\Xml\Dom\Document;

final class SoapHeaderMiddleware implements Plugin
{
    /**
     * @var list<callable(DOMNode): DOMElement>
     */
    private array $configurators;

    /**
     * @no-named-arguments
     * @param list<callable(DOMNode): DOMElement> $configurators
     */
    public function __construct(callable ... $configurators)
    {
        $this->configurators = $configurators;
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next((new XmlMessageManipulator)(
            $request,
            function (Document $document) {
                /** @var list<DOMElement> $headers */
                $headers = $document->build(new SoapHeaders(...$this->configurators));

                return $document->manipulate(new PrependSoapHeaders(...$headers));
            }
        ));
    }
}
