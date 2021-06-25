<?php

namespace Soap\Psr18Transport\Middleware\Wsdl;

use DOMElement;
use DOMNode;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use Soap\Xml\Xpath\WsdlPreset;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Manipulator\Node\remove;
use function VeeWee\Xml\Dom\Xpath\Configurator\namespaces;

class DisablePoliciesMiddleware implements Plugin
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
        $xpath->query('//wsd:UsingPolicy')->forEach(
            static fn (DOMElement $element): DOMNode => remove($element)
        );

        // remove all "Policy" tags
        $xpath->query('//wsd:Policy')->forEach(
            static fn (DOMElement $element): DOMNode => remove($element)
        );
    }
}
