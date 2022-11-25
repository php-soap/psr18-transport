<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\Integration;

use GuzzleHttp\Client;
use Soap\Engine\Engine;
use Soap\Engine\SimpleEngine;
use Soap\EngineIntegrationTests\AbstractEngineTest;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\ExtSoapDriver;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\Psr18Transport\Psr18Transport;

final class Psr18TransportTest extends AbstractEngineTest
{
    private Engine $engine;

    protected function getEngine(): Engine
    {
        return $this->engine;
    }

    protected function getVcrPrefix(): string
    {
        return 'ext-soap-with-psr18-transport-';
    }

    protected function skipVcr(): bool
    {
        return true;
    }

    public function test_it_should_be_possible_to_hook_php_vcr_for_testing()
    {
        static::markTestSkipped('PHP VCR is not in a good shape anymore');
    }

    protected function configureForWsdl(string $wsdl)
    {
        $this->engine = new SimpleEngine(
            ExtSoapDriver::createFromClient(
                AbusedClient::createFromOptions(
                    ExtSoapOptions::defaults($wsdl, [
                        'cache_wsdl' => WSDL_CACHE_NONE,
                        'soap_version' => SOAP_1_2,
                    ])
                )
            ),
            Psr18Transport::createForClient(
                new Client([
                    'headers' => [
                        'User-Agent' => 'testing/1.0',
                    ],
                ])
            )
        );
    }
}
