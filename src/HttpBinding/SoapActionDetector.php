<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\HttpBinding;

use Http\Client\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

final class SoapActionDetector
{
    public static function detectFromRequest(RequestInterface $request): string
    {
        $header = $request->getHeader('SOAPAction');
        if ($header) {
            return $header[0];
        }

        $contentTypes = $request->getHeader('Content-Type');
        if ($contentTypes) {
            $contentType = $contentTypes[0];
            foreach (explode(';', $contentType) as $part) {
                if (strpos($part, 'action=') !== false) {
                    return trim(explode('=', $part)[1], '"\'');
                }
            }
        }

        throw new RequestException('SOAP Action not found in HTTP headers.', $request);
    }
}
