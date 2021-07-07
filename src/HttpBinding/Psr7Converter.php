<?php declare(strict_types=1);

namespace Soap\Psr18Transport\HttpBinding;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Soap\Engine\HttpBinding\SoapRequest;
use Soap\Engine\HttpBinding\SoapResponse;

final class Psr7Converter
{
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    public function convertSoapRequest(SoapRequest $request): RequestInterface
    {
        $builder = new Psr7RequestBuilder($this->requestFactory, $this->streamFactory);

        $request->isSOAP11() ? $builder->isSOAP11() : $builder->isSOAP12();
        $builder->setEndpoint($request->getLocation());
        $builder->setSoapAction($request->getAction());
        $builder->setSoapMessage($request->getRequest());

        return $builder->getHttpRequest();
    }

    public function convertSoapResponse(ResponseInterface $response): SoapResponse
    {
        return new SoapResponse(
            (string) $response->getBody()
        );
    }
}
