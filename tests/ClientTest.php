<?php

namespace Http\Adapter\Zend\Tests;

use Http\Adapter\Zend\Client;
use Http\Client\Tests\HttpClientTest;

class ClientTest extends HttpClientTest
{
    protected function createHttpAdapter()
    {
        return new Client();
    }
}
