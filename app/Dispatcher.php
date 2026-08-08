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
            throw new RuntimeException("Action not found: {$route->controller}::{$method}().", 404);
        }

        $reflection = new ReflectionMethod($controller, $method);
        if (!$reflection->isPublic()) {
            throw new RuntimeException("Action is not public: {$route->controller}::{$method}().", 404);
        }

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (array_key_exists($name, $route->parameters)) {
                $value = $route->parameters[$name];
                $arguments[] = $this->resolveHttpParameter($value, $type, $name);
                continue;
            }

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->container->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new RuntimeException(
                "Missing required parameter '{$name}' for {$route->controller}::{$method}().",
                400
            );
        }

        return $reflection->invokeArgs($controller, $arguments);
    }

    private function resolveHttpParameter(mixed $value, ?\ReflectionType $type, string $name): mixed
    {
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            throw new RuntimeException(
                "Route parameter '{$name}' cannot be injected into a class-typed argument.",
                400
            );
        }

        return match ($type->getName()) {
            'int' => $this->toInt($value, $name),
            'float' => $this->toFloat($value, $name),
            'bool' => $this->toBool($value, $name),
            'string' => (string) $value,
            default => $value,
        };
    }

    private function toInt(mixed $value, string $name): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException("Invalid integer parameter '{$name}'.", 400);
        }
        return (int) $value;
    }

    private function toFloat(mixed $value, string $name): float
    {
        if (!is_numeric($value)) {
            throw new RuntimeException("Invalid float parameter '{$name}'.", 400);
        }
        return (float) $value;
    }

    private function toBool(mixed $value, string $name): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($result === null) {
            throw new RuntimeException("Invalid boolean parameter '{$name}'.", 400);
        }
        return $result;
    }
}
