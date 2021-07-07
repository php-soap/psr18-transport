<?php declare(strict_types=1);

namespace Soap\Psr18Transport\Middleware;

use DOMNode;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use Soap\Xml\Xpath\EnvelopePreset;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;

final class RemoveEmptyNodesMiddleware implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $request = (new XmlMessageManipulator())(
            $request,
            static function (Document $xml): void {
                $xpath = $xml->xpath(new EnvelopePreset($xml));

                do {
                    $emptyNodes = $xpath->query('//soap:Envelope/*//*[not(node())]');
                    $emptyNodes->forEach(
                        static fn (DOMNode $element) => remove($element)
                    );
                } while ($emptyNodes->count());
            }
        );

        return $next($request);
    }
}
