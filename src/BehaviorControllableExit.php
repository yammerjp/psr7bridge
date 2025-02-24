<?php

namespace Yammerjp\Psr7bridge;

class BehaviorControllableExit
{
    private static $instance;
    private $callback;

    public function __construct(?callable $callback)
    {
        $this->callback = $callback ?? function ($status = 0) {
            exit($status);
        };
    }

    public function exit($status = 0)
    {
        call_user_func($this->callback, $status);
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self(null);
        }
        return self::$instance;
    }

    public static function setCallback(?callable $callback)
    {
        self::$instance->callback = $callback;
    }

    public static function getCallback(): ?callable
    {
        return self::getInstance()->callback;
    }
}
