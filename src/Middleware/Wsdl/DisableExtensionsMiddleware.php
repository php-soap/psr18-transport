<?php declare(strict_types=1);

namespace Soap\Psr18Transport\Middleware\Wsdl;

use DOMElement;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Builder\namespaced_attribute;
use function VeeWee\Xml\Dom\Locator\root_namespace_uri;

final class DisableExtensionsMiddleware implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)
            ->then(function (ResponseInterface $response): ResponseInterface {
                return (new XmlMessageManipulator)(
                    $response,
                    fn (Document $document) => $this->disableExtensions($document)
                );
            });
    }

    private function disableExtensions(Document $document): void
    {
        $namespace = $document->locate(root_namespace_uri());
        $document->xpath(new WsdlPreset($document))
            ->query('//wsdl:binding//*[@wsdl:required]')
            ->expectAllOfType(DOMElement::class)
            ->forEach(
                static function (DOMElement $element) use ($namespace): void {
                    namespaced_attribute($namespace ?? '', 'wsdl:required', 'false')($element);
                }
            );
    }
}
