<?php

namespace Http\Adapter\Zend;

use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

class Client implements HttpClient
{
    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
    }
}
