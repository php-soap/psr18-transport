# PSR-18 SOAP Transport

This transport allows you to send SOAP requests over a PSR-18 HTTP client implementation.
You can use any client you want, going from curl, guzzle, httplug, symfony/http-client, ...
It allows you to get full control over the HTTP layer, making it possible to e.g. overcome some well-known issues in `ext-soap`.
This package can best be used together with a [SOAP driver](https://github.com/php-soap/engine) that handles data encoding and decoding.

# Want to help out? 💚

- [Become a Sponsor](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#sponsor)
- [Let us do your implementation](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#let-us-do-your-implementation)
- [Contribute](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#contribute)
- [Help maintain these packages](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#maintain)

Want more information about the future of this project? Check out this list of the [next big projects](https://github.com/php-soap/.github/blob/main/PROJECTS.md) we'll be working on.

# Prerequisites

Choosing what HTTP client you want to use.
This package expects some PSR implementations to be present in order to be installed:

* PSR-7: `psr/http-message-implementation` like `nyholm/psr7` or `guzzlehttp/psr7`
* PSR-17: `psr/http-factory-implementation` like `nyholm/psr7` or `guzzlehttp/psr7`
* PSR-18: `psr/http-client-implementation` like `symfony/http-client` or `guzzlehttp/guzzle`

# Installation

```bash
composer require php-soap/psr18-transport
```

## Usage

```php

use Http\Client\Common\PluginClient;
use Soap\Engine\SimpleEngine;
use Soap\Psr18Transport\Psr18Transport;

$engine = new SimpleEngine(
    $driver,
    $transport = Psr18Transport::createForClient(
        new PluginClient(
            $psr18Client,
            [...$middleware]
        )
    )
);
```

## Middleware

This package provides some middleware implementations for dealing with some common SOAP issues.

### Wsdl\DisableExtensionsMiddleware

PHP's ext-soap implementation do not support `wsdl:required` attributes since there is no SOAP extension mechanism in PHP.
You will retrieve this exception: "[SoapFault] SOAP-ERROR: Parsing WSDL: Unknown required WSDL extension"
when the WSDL does contain required SOAP extensions.

This middleware can be used to set the "wsdl:required"
property to false when loading the WSDL so that you don't have to change the WSDL on the server.

**Usage**

```php
use Http\Client\Common\PluginClient;
use Soap\Psr18Transport\Middleware\Wsdl\DisableExtensionsMiddleware;

$wsdlClient = new PluginClient(
    $psr18Client,
    [
        new DisableExtensionsMiddleware(),
    ]
);
```

### Wsdl\DisablePoliciesMiddleware

PHP's ext-soap client does not support the [Web Services Policy Framework](http://schemas.xmlsoap.org/ws/2004/09/policy/) attributes since there is no such support in PHP.
You will retrieve this exception: "[SoapFault] SOAP-ERROR: Parsing WSDL: Unknown required WSDL extension 'http://schemas.xmlsoap.org/ws/2004/09/policy'"
when the WSDL does contains WS policies.

This middleware can be used to remove all UsingPolicy and Policy tags on the fly so that you don't have to change the WSDL on the server.

**Usage**

```php
use Http\Client\Common\PluginClient;
use Soap\Psr18Transport\Middleware\Wsdl\DisablePoliciesMiddleware;

$wsdlClient = new PluginClient(
    $psr18Client,
    [
        new DisablePoliciesMiddleware(),
    ]
);
```

### RemoveEmptyNodesMiddleware

Empty properties are converted into empty nodes in the request XML.
If you need to avoid empty nodes in the request xml, you can add this middleware.

**Usage**

```php
use Http\Client\Common\PluginClient;
use Soap\Psr18Transport\Middleware\RemoveEmptyNodesMiddleware;


$httpClient = new PluginClient(
    $psr18Client,
    [
        new RemoveEmptyNodesMiddleware()
    ]
);
```

### SoapHeaderMiddleware

Attaches multiple SOAP headers to the request before sending the SOAP envelope.

**Usage**

```php
use Http\Client\Common\PluginClient;
use Soap\Psr18Transport\Middleware\RemoveEmptyNodesMiddleware;
use Soap\Xml\Builder\Header\Actor;
use Soap\Xml\Builder\Header\MustUnderstand;
use Soap\Xml\Builder\SoapHeader;


$httpClient = new PluginClient(
    $psr18Client,
    [
        new SoapHeaderMiddleware(
            new SoapHeader(
                $tns,
                'x:Auth',
                children(
                    namespaced_element($tns, 'x:user', value('josbos')),
                    namespaced_element($tns, 'x:password', value('topsecret'))
                )
            ),
            new SoapHeader($tns, 'Acting', Actor::next()),
            new SoapHeader($tns, 'Understanding', new MustUnderstand())
        )
    ]
);
```

More information on the SoapHeader configurator can be found in [php-soap/xml](https://github.com/php-soap/xml#soapheaders).

### HTTPlug middleware

This package includes [all basic plugins from httplug](https://docs.php-http.org/en/latest/plugins/).
You can load any additional plugins you want, like e.g. [the logger plugin](https://github.com/php-http/logger-plugin).

**Examples**

```php
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Message\Authentication\BasicAuth;


$httpClient = new PluginClient(
    $psr18Client,
    [
        new BaseUriPlugin($baseLocation),
        new AuthenticationPlugin(new BasicAuth($_ENV['user'], $_ENV['pass'])),
        new LoggerPlugin($psrLogger),
    ]
);
```

### Writing your own middleware

We use httplug for its plugin system.
You can create your own middleware by [following their documentation](https://docs.php-http.org/en/latest/plugins/build-your-own.html).

## Authentication

You can add authentication to both the WSDL fetching and SOAP handling part.
For this, we suggest you to use the default [httplug authentication providers](https://docs.php-http.org/en/latest/message/authentication.html).

### NTLM

Adding NTLM authentication requires you to use a `curl` based PSR-18 HTTP Client.
On those clients, you can set following options: `[CURLOPT_HTTPAUTH => CURLAUTH_NTLM, CURLOPT_USERPWD => 'user:pass']`.
Clients like guzzle and symfony/http-client also support NTLM by setting options during client configuration.

## Dealing with XML

When writing custom SOAP middleware, a frequent task is to transform the request or response XML into a slight variation.
This package provides some shortcut tools around [php-soap/xml](https://github.com/php-soap/xml) to make it easier for you to deal with XML.


**Example**

```php
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Soap\Psr18Transport\Xml\XmlMessageManipulator;
use VeeWee\Xml\Dom\Document;

class SomeMiddleware implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $request = (new XmlMessageManipulator)(
            $request,
            fn (Document $document) => $document->manipulate(
                doSomethingWithRequestXml()
            )
        );
    
        return $next($request)
            ->then(function (ResponseInterface $response): ResponseInterface {
                return (new XmlMessageManipulator)(
                    $response,
                    fn (Document $document) => $document->manipulate(
                        doSomethingWithResponseXml()
                    )
                );
            });
    }
}
```

## Loading WSDL with PSR-18 clients

For loading WSDL's, you might want to use a PSR-18 client to do the hard HTTP work.
This allows you for advances setups in which the WSDL is put behind basic authentication.
This package provides a PSR-18 [WSDL loader](https://github.com/php-soap/wsdl#wsdl-loader) that can be used to load HTTP locations with your favourite HTTP client.
It can be used in combinations with for example the WSDL loaders from the [php-soap/ext-soap-engine](https://github.com/php-soap/ext-soap-engine).

### Psr18Loader

**Examples**

```php
use Http\Client\Common\PluginClient;
use Soap\Psr18Transport\Wsdl\Psr18Loader;
use Soap\Wsdl\Loader\FlatteningLoader;

$loader = Psr18Loader::createForClient(
    $wsdlClient = new PluginClient(
        $psr18Client,
        [...$middleware]
    )
);

// If you want to flatten all imports whilst using this PSR-18 loader:
$loader = new FlatteningLoader($loader);


$payload = $loader('http://some.wsdl');
```

*NOTE:* If you want to flatten the imports inside the WSDL, you'll have to combine this loader with the the [FlatteningLoader](https://github.com/php-soap/wsdl#flatteningloader).


## Async SOAP calls

Since PHP 8.1, fibers are introduced to PHP.
This means that you can use any fiber based PSR-18 client in order to send async calls.

Here is a short example for `react/http` in combination with `react/async`.

```sh
composer require react/async veewee/psr18-react-browser
```

*(There currently is no official fiber based PSR-18 implementation of either AMP or ReactPHP. Therefore, [a small bridge can be used intermediately](https://github.com/veewee/psr18-react-browser))*


Usage:

```php
use Http\Client\Common\PluginClient;
use Soap\Engine\SimpleEngine;
use Soap\ExtSoapEngine\ExtSoapDriver;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\Wsdl\TemporaryWsdlLoaderProvider;
use Soap\Psr18Transport\Psr18Transport;
use Soap\Psr18Transport\Wsdl\Psr18Loader;
use Soap\Wsdl\Loader\FlatteningLoader;
use Veewee\Psr18ReactBrowser\Psr18ReactBrowserClient;
use function React\Async\async;
use function React\Async\await;
use function React\Async\parallel;

$asyncHttpClient = Psr18ReactBrowserClient::default();
$engine = new SimpleEngine(
    ExtSoapDriver::createFromClient(
        $client = AbusedClient::createFromOptions(
            ExtSoapOptions::defaults('http://www.dneonline.com/calculator.asmx?wsdl', [])
                ->disableWsdlCache()
        )
    ),
    $transport = Psr18Transport::createForClient(
        new PluginClient(
            $asyncHttpClient,
            [...$middleware]
        )
    )
);

$add = async(fn ($a, $b) => $engine->request('Add', [['intA' => $a, 'intB' => $b]]));
$addWithLogger = fn ($a, $b) => $add($a, $b)->then(
    function ($result) use ($a, $b) {
        echo "SUCCESS {$a}+{$b} = ${result}!" . PHP_EOL;
        return $result;
    },
    function (Exception $e) {
        echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    }
);

$results = await(parallel([
    fn() => $addWithLogger(1, 2),
    fn() => $addWithLogger(3, 4),
    fn() => $addWithLogger(5, 6)
]));

var_dump($results);
```
