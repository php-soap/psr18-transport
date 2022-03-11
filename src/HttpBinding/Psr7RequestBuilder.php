<?php declare(strict_types=1);

namespace Soap\Psr18Transport\HttpBinding;

use InvalidArgumentException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Soap\Psr18Transport\Exception\RequestException;

/**
 * Class Psr7RequestBuilder
 *
 * @package Soap\Psr18Transport\HttpBinding\Builder
 * @link https://github.com/meng-tian/soap-http-binding
 */
final class Psr7RequestBuilder
{
    const SOAP11 = '1.1';
    const SOAP12 = '1.2';

    private string $endpoint = '';
    private string $soapVersion = self::SOAP11;
    private string $soapAction = '';
    private ?StreamInterface $soapMessage = null;
    private bool $hasSoapMessage = false;
    private string $httpMethod = 'POST';

    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @throws RequestException
     */
    public function getHttpRequest(): RequestInterface
    {
        $this->validate();

        try {
            $request = $this->requestFactory
                ->createRequest($this->httpMethod, $this->endpoint)
                ->withBody($this->prepareMessage());

            foreach ($this->prepareHeaders() as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        } catch (InvalidArgumentException $e) {
            throw RequestException::fromException($e);
        }

        return $request;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Mark as SOAP 1.1
     */
    public function isSOAP11(): void
    {
        $this->soapVersion = self::SOAP11;
    }

    /**
     * Mark as SOAP 1.2
     */
    public function isSOAP12(): void
    {
        $this->soapVersion = self::SOAP12;
    }


    
    public function setSoapAction(string $soapAction): void
    {
        $this->soapAction = $soapAction;
    }

    public function setSoapMessage(string $content): void
    {
        $this->soapMessage = $this->streamFactory->createStream($content);
        $this->hasSoapMessage = true;
    }

    public function setHttpMethod(string $method): void
    {
        $this->httpMethod = $method;
    }

    /**
     * @throws RequestException
     */
    private function validate(): void
    {
        if (!$this->endpoint) {
            throw RequestException::noEndpoint();
        }

        if (!$this->hasSoapMessage && $this->httpMethod === 'POST') {
            throw RequestException::noMessage();
        }

        /**
         * SOAP 1.1 only defines HTTP binding with POST method.
         * @link https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383527
         */
        if ($this->soapVersion === self::SOAP11 && $this->httpMethod !== 'POST') {
            throw RequestException::postNotAllowedForSoap11();
        }

        /**
         * SOAP 1.2 only defines HTTP binding with POST and GET methods.
         * @link https://www.w3.org/TR/2007/REC-soap12-part0-20070427/#L10309
         */
        if ($this->soapVersion === self::SOAP12 && !in_array($this->httpMethod, ['GET', 'POST'], true)) {
            throw RequestException::invalidMethodForSoap12();
        }
    }

    /**
     * @return array<string, string>
     */
    private function prepareHeaders(): array
    {
        if ($this->soapVersion === self::SOAP11) {
            return $this->prepareSoap11Headers();
        }

        return $this->prepareSoap12Headers();
    }

    /**
     * @link https://www.w3.org/TR/2000/NOTE-SOAP-20000508/#_Toc478383526
     * @return array<string, string>
     */
    private function prepareSoap11Headers(): array
    {
        $headers = [];
        $headers['SOAPAction'] = $this->prepareQuotedSoapAction($this->soapAction);
        $headers['Content-Type'] = 'text/xml; charset="utf-8"';

        return array_filter($headers);
    }

    /**
     * SOAPAction header is removed in SOAP 1.2 and now expressed as a value of
     * an (optional) "action" parameter of the "application/soap+xml" media type.
     * @link https://www.w3.org/TR/soap12-part0/#L4697
     * @return array<string, string>
     */
    private function prepareSoap12Headers(): array
    {
        $headers = [];
        if ($this->httpMethod !== 'POST') {
            $headers['Accept'] = 'application/soap+xml';
            return $headers;
        }

        $soapAction = $this->prepareQuotedSoapAction($this->soapAction);
        $headers['Content-Type'] = 'application/soap+xml; charset="utf-8"' . '; action='.$soapAction;

        return array_filter($headers);
    }

    private function prepareMessage(): StreamInterface
    {
        if ($this->httpMethod === 'POST') {
            return $this->soapMessage ?? $this->streamFactory->createStream('');
        }

        return $this->streamFactory->createStream('');
    }

    private function prepareQuotedSoapAction(string $soapAction): string
    {
        $soapAction = trim($soapAction, '"\'');

        return '"'.$soapAction.'"';
    }
}
