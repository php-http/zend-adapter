<?php

namespace Http\Adapter\Zend;

use Http\Client\Exception\NetworkException;
use Http\Client\HttpClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\ResponseFactory;
use Psr\Http\Message\RequestInterface;
use Zend\Http\Client as ZendClient;
use Zend\Http\Exception\RuntimeException;
use Zend\Http\Header\GenericHeader;
use Zend\Http\Headers;
use Zend\Http\Request;

class Client implements HttpClient
{
    /** @var ZendClient */
    private $client;

    /** @var ResponseFactory */
    private $responseFactory;

    public function __construct(ZendClient $client = null, ResponseFactory $responseFactory = null)
    {
        $this->client = $client ?: new ZendClient(null, [
            'maxredirects' => 0,
            'storeresponse' => false,
        ]);
        $this->responseFactory = $responseFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $headers = new Headers();

        foreach ($request->getHeaders() as $key => $value) {
            $headers->addHeader(new GenericHeader($key, $request->getHeaderLine($key)));
        }

        $zendRequest = new Request();
        $zendRequest->setMethod($request->getMethod());
        $zendRequest->setUri((string) $request->getUri());
        $zendRequest->setVersion($request->getProtocolVersion());
        $zendRequest->setHeaders($headers);
        $zendRequest->setContent($request->getBody()->getContents());

        try {
            $zendResponse = $this->client->send($zendRequest);
        } catch (RuntimeException $exception) {
            throw new NetworkException($exception->getMessage(), $request, $exception);
        }

        return $this->responseFactory->createResponse(
            $zendResponse->getStatusCode(),
            $zendResponse->getReasonPhrase(),
            $zendResponse->getHeaders()->toArray(),
            $zendResponse->getContent(),
            $zendResponse->getVersion()
        );
    }
}
