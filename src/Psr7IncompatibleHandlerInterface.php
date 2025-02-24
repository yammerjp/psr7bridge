<?php

namespace Yammerjp\Psr7bridge;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Psr7IncompatibleHandlerInterface
{
    public function handle(ServerRequestInterface $request): ?ResponseInterface;
}
