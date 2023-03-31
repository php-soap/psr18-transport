<?php declare(strict_types=1);

namespace Soap\Psr18Transport\Middleware\Wsdl;

use DOMElement;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;

final class DisablePoliciesMiddleware implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)
            ->then(function (ResponseInterface $response): ResponseInterface {
                return (new XmlMessageManipulator)(
                    $response,
                    fn (Document $document) => $this->disablePolicies($document)
                );
            });
    }

    public function disablePolicies(Document $document): void
    {
        $xpath = $document->xpath(
            namespaces([
                'wsd' => 'http://schemas.xmlsoap.org/ws/2004/09/policy'
            ])
        );

        // remove all "UsingPolicy" tags
        $xpath->query('//wsd:UsingPolicy')
            ->expectAllOfType(DOMElement::class)
            ->forEach(
                static function (DOMElement $element): void {
                    remove($element);
                }
            );

        // remove all "Policy" tags
        $xpath->query('//wsd:Policy')
            ->expectAllOfType(DOMElement::class)
            ->forEach(
                static function (DOMElement $element): void {
                    remove($element);
                }
            );
    }
}
