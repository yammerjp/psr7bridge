<?php

namespace Yammerjp\Psr7bridgeexample;

use Yammerjp\Psr7bridge\Psr7CompatibleHandler;
use Yammerjp\Psr7bridgeexample\LegacyHandler;

class App
{
    public function run()
    {
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $serverRequest = $creator->fromGlobals();

        $incompatibleHandler = new LegacyHandler($psr17Factory, $psr17Factory);
        $requestHandler = new Psr7CompatibleHandler($psr17Factory, $psr17Factory, $incompatibleHandler);

        $response = $requestHandler->handle($serverRequest);
        (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
    }
}
