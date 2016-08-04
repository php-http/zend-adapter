<?php

namespace Http\Adapter\Zend\Tests;

use Zend\Http\Client\Adapter\Curl;

class CurlClientTest extends ClientTest
{
    protected function getZendAdapter()
    {
        return Curl::class;
    }

    protected function shouldBeSkip($method, $uri, array $headers, $body)
    {
        if (strlen($body) !== 0 && !in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            return 'Zend Curl Adapter does not support body in other method than POST, PUT or PATCH';
        }

        return parent::shouldBeSkip($method, $uri, $headers, $body);
    }
}
