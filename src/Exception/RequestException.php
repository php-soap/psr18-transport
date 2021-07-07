<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Exception;

use Soap\Engine\Exception\RuntimeException;
use Throwable;

final class RequestException extends RuntimeException
{
    public static function noEndpoint(): self
    {
        return new self('There is no endpoint specified.');
    }

    public static function noMessage(): self
    {
        return new self('There is no SOAP message specified.');
    }

    public static function postNotAllowedForSoap11(): self
    {
        return new self('You cannot use the POST method with SOAP 1.1.');
    }

    public static function invalidMethodForSoap12(): self
    {
        return new self('Invalid SOAP method specified for SOAP 1.2. Expected: GET or POST.');
    }

    public static function fromException(Throwable $exception): self
    {
        return new self($exception->getMessage(), (int) $exception->getCode(), $exception);
    }
}
