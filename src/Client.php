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
        $this->client = $client ?: new ZendClient();
        $this->responseFactory = $responseFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $request = $this->sanitizeRequest($request);
        $headers = new Headers();

        foreach ($request->getHeaders() as $key => $value) {
            $headers->addHeader(new GenericHeader($key, $request->getHeaderLine($key)));
        }

        $zendRequest = new Request();
        $zendRequest->setMethod($request->getMethod());
        $zendRequest->setUri((string) $request->getUri());
        $zendRequest->setHeaders($headers);
        $zendRequest->setContent($request->getBody()->getContents());

        $options = [
            'httpversion' => $request->getProtocolVersion(),
        ];

        if (extension_loaded('curl')) {
            $options['curloptions'] = [
                CURLOPT_HTTP_VERSION => $this->getProtocolVersion($request->getProtocolVersion()),
            ];
        }

        $this->client->setOptions($options);

        if ($this->client->getAdapter() instanceof ZendClient\Adapter\Curl && $request->getMethod()) {
            $request = $request->withHeader('Content-Length', '0');
        }

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

    private function sanitizeRequest(RequestInterface $request)
    {
        $request = $this->sanitizeWithTrace($request);
        $request = $this->sanitizeWithCurl($request);

        return $request;
    }

    /**
     * Zend request remove the body if it's a trace but does not rewrite the content length header,
     * This can lead to error from the server has it can expect a specific content length, but don't have
     * a body, so we set content-length to 0 to avoid bad reading from the server.
     *
     * @param RequestInterface $request
     *
     * @return RequestInterface|static
     */
    private function sanitizeWithTrace(RequestInterface $request)
    {
        if ($request->getMethod() === 'TRACE') {
            $request = $request->withHeader('Content-Length', '0');
        }

        return $request;
    }

    /**
     * On cUrl Adapter, zend does not include the body if it's not a POST, PUT or PATCH request but does not
     * rewrite the content length header.
     * This can lead to error from the server has it can expect a specific content length, but don't have
     * a body, so we set content-length to 0 to avoid bad reading from the server.
     *
     * @param RequestInterface $request
     *
     * @return RequestInterface|static
     */
    private function sanitizeWithCurl(RequestInterface $request)
    {
        if ($this->client->getAdapter() instanceof ZendClient\Adapter\Curl && !in_array($request->getMethod(), [
                'POST',
                'PUT',
                'PATCH',
            ], true)) {
            $request = $request->withHeader('Content-Length', '0');
        }

        return $request;
    }

    /**
     * Return cURL constant for specified HTTP version.
     *
     * @param string $requestVersion
     *
     * @throws \UnexpectedValueException if unsupported version requested
     *
     * @return int
     */
    private function getProtocolVersion($requestVersion)
    {
        switch ($requestVersion) {
            case '1.0':
                return CURL_HTTP_VERSION_1_0;
            case '1.1':
                return CURL_HTTP_VERSION_1_1;
            case '2.0':
                if (defined('CURL_HTTP_VERSION_2_0')) {
                    return CURL_HTTP_VERSION_2_0;
                }
                throw new \UnexpectedValueException('libcurl 7.33 needed for HTTP 2.0 support');
        }

        return CURL_HTTP_VERSION_NONE;
    }
}
