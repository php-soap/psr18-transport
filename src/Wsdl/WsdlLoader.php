<?php

declare(strict_types=1);

namespace Soap\Psr18Transport\Wsdl;

/**
 * Loads the content of a WSDL location
 */
interface WsdlLoader
{
    public function __invoke(string $location): string;
}
