<?php
declare(strict_types=1);

namespace SoapTest\Psr18Transport\Unit\Wsdl;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Soap\Psr18Transport\Wsdl\Psr18Loader;

class Psr18LoaderTest extends TestCase
{
    /** @test */
    public function it_can_load_wsdl_through_psr18_client(): void
    {
        $expected = '<definitions />';
        $client = new Client();
        $client->setDefaultException(new RuntimeException('Loaded URL path did not match!'));
        $client->on(
            new RequestMatcher('/schema.wsdl', methods: ['GET']),
            static fn () => Psr17FactoryDiscovery::findResponseFactory()->createResponse()->withBody(
                Psr17FactoryDiscovery::findStreamFactory()->createStream($expected)
            )
        );

        $loader = Psr18Loader::createForClient($client);
        $actual = $loader('http://some.com/schema.wsdl');

        self::assertSame($expected, $actual);
    }
}
