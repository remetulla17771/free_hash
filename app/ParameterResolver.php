<?php

declare(strict_types=1);

namespace app;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use RuntimeException;

final class ParameterResolver
{
    public function __construct(private Container $container)
    {
    }

    public function resolve(ReflectionParameter $parameter, array $routeParameters): mixed
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if (array_key_exists($name, $routeParameters)) {
            return $this->resolveHttpParameter($routeParameters[$name], $type, $name);
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->container->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new RuntimeException("Missing required parameter '{$name}'.", 400);
    }

    private function resolveHttpParameter(mixed $value, ?ReflectionType $type, string $name): mixed
    {
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            throw new RuntimeException("Route parameter '{$name}' cannot be injected into a class-typed argument.", 400);
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
