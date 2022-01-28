<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Middleware\WSICompliance;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Soap\Psr18Transport\HttpBinding\SoapActionDetector;

/**
 * @deprecated The Psr7RequestBuilder now applies this logic automatically. This will be released in v2.x !
 *
 * @see http://www.ws-i.org/Profiles/BasicProfile-1.0-2004-04-16.html#R2744
 *
 * Fixes error:
 *
 *  WS-I Compliance failure (R2744):
 *  The value of the SOAPAction transport header must be double-quoted.
 */
final class QuotedSoapActionMiddleware implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $soapAction = SoapActionDetector::detectFromRequest($request);
        $soapAction = trim($soapAction, '"\'');

        return $next($request->withHeader('SOAPAction', '"'.$soapAction.'"'));
    }
}
