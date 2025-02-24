<?php

namespace Yammerjp\Psr7bridge;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseBuilder
{
    private $responseFactory;
    private $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    public function build(array $headerLines, string $output): ResponseInterface
    {
        $status = 200;
        $headers = [];
        foreach ($headerLines as $headerLine) {
            // 'HTTP/'
            if (strpos($headerLine, 'HTTP/') === 0) {
                $status = substr($headerLine, 9, 3);
            }
            [$key, $value] = explode(':', $headerLine, 2);
            $headers[$key] = $value;
        }
        $response = $this->responseFactory->createResponse($status);
        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }
        $response->getBody()->write($output);
        return $response;
    }
}
