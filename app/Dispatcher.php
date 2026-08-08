<?php

declare(strict_types=1);

namespace app;

use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

final class Dispatcher
{
    public function __construct(private Container $container)
    {
    }

    public function dispatch(Route $route): mixed
    {
        $controller = $this->container->get($route->controller);
        $method = $route->action;

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Action not found: {$method}", 404);
        }

        $reflection = new ReflectionMethod($controller, $method);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $route->parameters)) {
                $value = $route->parameters[$name];
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    $value = $this->cast($value, $type->getName());
                }

                $arguments[] = $value;
                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->container->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Missing required parameter '{$name}' for {$route->controller}::{$method}().",
                400
            );
        }

        return $reflection->invokeArgs($controller, $arguments);
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'string' => (string) $value,
            default => $value,
        };
    }
}
