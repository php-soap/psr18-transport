<?php declare(strict_types=1);

namespace Soap\Psr18Transport\Middleware;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use VeeWee\Xml\Dom\Document;
use function VeeWee\Xml\Dom\Manipulator\Document\promote_namespaces;

final class PromoteNamespacesMiddleware implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $request = (new XmlMessageManipulator())(
            $request,
            static function (Document $document): void {
                promote_namespaces($document->toUnsafeDocument());
            }
        );

        return $next($request);
    }
}
