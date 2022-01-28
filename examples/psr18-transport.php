<?php

use Soap\Engine\SimpleEngine;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\ExtSoapDriver;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\Transport\TraceableTransport;
use Soap\Psr18Transport\Psr18Transport;

require_once dirname(__DIR__).'/vendor/autoload.php';

$engine = new SimpleEngine(
    ExtSoapDriver::createFromClient(
        $client = AbusedClient::createFromOptions(
            ExtSoapOptions::defaults('http://www.dneonline.com/calculator.asmx?wsdl', [])
                ->disableWsdlCache()
        )
    ),
    $transport = new TraceableTransport(
        $client,
        Psr18Transport::createForClient(new \GuzzleHttp\Client())
    )
);

$result = $engine->request('Add', [['intA' => 1, 'intB' => 2]]);

var_dump($result);
var_dump($transport->collectLastRequestInfo());
