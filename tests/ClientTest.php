<?php

namespace Http\Adapter\Zend\Tests;

use Http\Adapter\Zend\Client;
use Http\Client\Tests\HttpClientTest;
use Zend\Http\Client as ZendClient;

abstract class ClientTest extends HttpClientTest
{
    protected function createHttpAdapter()
    {
        return new Client(new ZendClient(null, [
            'adapter' => $this->getZendAdapter(),
            'maxredirects' => 0,
            'storeresponse' => false,
        ]));
    }

    abstract protected function getZendAdapter();

    protected function shouldBeSkip($method, $uri, array $headers, $body)
    {
        if ($method === 'TRACE' && strlen($body) > 0) {
            return "Zend Http Adapter does not work well with TRACE method and a BODY";
        }

        return false;
    }
}
