<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

final class ControllerResolver
{
    public function __construct(private Container $container)
    {
    }

    public function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Controller class '{$class}' not found.", 404);
        }

        $controller = $this->container->get($class);

        if (!is_object($controller)) {
            throw new RuntimeException("Unable to resolve controller '{$class}'.", 500);
        }

        return $controller;
    }
}
