<?php

namespace Yammerjp\Psr7bridge;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yammerjp\Psr7bridge\BehaviorControllableExit;

class Psr7CompatibleHandler implements RequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private Psr7IncompatibleHandlerInterface $incompatibleHandler;
    private ResponseBuilder $responseBuilder;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        Psr7IncompatibleHandlerInterface $incompatibleHandler
    )
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->incompatibleHandler = $incompatibleHandler;
        $this->responseBuilder = new ResponseBuilder($responseFactory, $streamFactory);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $previousExitCallback = BehaviorControllableExit::getCallback();
        BehaviorControllableExit::setCallback(function () {
            throw new ExitException();
        });

        ob_start();
        try {
            $response = $this->incompatibleHandler->handle($request);
        } catch (ExitException $_) {
            // do nothing
        }
        BehaviorControllableExit::setCallback($previousExitCallback);

        if ($response) {
            return $response;
        }

        $output = ob_get_clean();
        $headerLines = headers_list();
        return $this->responseBuilder->build($headerLines, $output);
    }
}
