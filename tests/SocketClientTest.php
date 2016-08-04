<?php

namespace Http\Adapter\Zend\Tests;

use Zend\Http\Client\Adapter\Socket;

class SocketClientTest extends ClientTest
{
    protected function getZendAdapter()
    {
        return Socket::class;
    }
}
