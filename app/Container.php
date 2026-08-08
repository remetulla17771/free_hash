<?php

declare(strict_types=1);

namespace app;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

/**
 * Minimal dependency injection container used by the application core.
 */
class Container
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $aliases = [];

    public function set(string $id, mixed $value): void
    {
        if (is_callable($value)) {
            $this->factories[$id] = $value;
            unset($this->instances[$id]);
            return;
        }

        $this->instances[$id] = $value;
        unset($this->factories[$id]);
    }

    public function singleton(string $id, mixed $value): void
    {
        $this->set($id, $value);
    }

    public function alias(string $id, string $target): void
    {
        $this->aliases[$id] = $target;
    }

    public function has(string $id): bool
    {
        $id = $this->resolveAlias($id);
        return isset($this->instances[$id])
            || isset($this->factories[$id])
            || class_exists($id);
    }

    /**
     * @throws ReflectionException
     */
    public function get(string $id): mixed
    {
        $id = $this->resolveAlias($id);

        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $instance = ($this->factories[$id])($this);
            $this->instances[$id] = $instance;
            unset($this->factories[$id]);
            return $instance;
        }

        if (!class_exists($id)) {
            throw new RuntimeException("Unable to resolve '{$id}'.");
        }

        $instance = $this->build($id);
        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * @throws ReflectionException
     */
    public function build(string $class): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class '{$class}' is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Unable to resolve constructor parameter '{$parameter->getName()}' "
                . "of '{$class}'."
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveAlias(string $id): string
    {
        $visited = [];
        while (isset($this->aliases[$id])) {
            if (isset($visited[$id])) {
                throw new RuntimeException("Circular container alias detected for '{$id}'.");
            }
            $visited[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }
}
