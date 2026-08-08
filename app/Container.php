<?php

declare(strict_types=1);

namespace app;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

/**
 * Small constructor-injection container.
 *
 * Unregistered classes are transient. Explicit singletons are shared.
 * Factories are transient and executed on every get().
 */
final class Container
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $aliases = [];

    public function set(string $id, mixed $value): void
    {
        $id = $this->resolveAlias($id);
        $this->instances[$id] = $value;
        unset($this->factories[$id]);
    }

    public function singleton(string $id, mixed $value): void
    {
        $this->set($id, $value);
    }

    public function factory(string $id, callable $factory): void
    {
        $id = $this->resolveAlias($id);
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function alias(string $id, string $target): void
    {
        if ($id === $target) {
            throw new RuntimeException("Container alias '{$id}' cannot point to itself.");
        }

        $this->aliases[$id] = $target;
    }

    public function has(string $id): bool
    {
        $id = $this->resolveAlias($id);

        return array_key_exists($id, $this->instances)
            || isset($this->factories[$id])
            || class_exists($id);
    }

    /** @throws ReflectionException */
    public function get(string $id): mixed
    {
        $id = $this->resolveAlias($id);

        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            $instance = ($this->factories[$id])($this);
            $this->assertResolvedType($id, $instance);
            return $instance;
        }

        if (!class_exists($id)) {
            throw new RuntimeException("Unable to resolve '{$id}'.");
        }

        return $this->build($id);
    }

    /** @throws ReflectionException */
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

            if ($type instanceof ReflectionUnionType) {
                throw new RuntimeException(
                    "Union-typed constructor parameter '{$parameter->getName()}' of '{$class}' "
                    . 'cannot be resolved automatically.'
                );
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "Unable to resolve constructor parameter '{$parameter->getName()}' of '{$class}'."
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

    private function assertResolvedType(string $id, mixed $instance): void
    {
        if ($instance === null) {
            return;
        }

        if (class_exists($id) && !$instance instanceof $id) {
            throw new RuntimeException("Factory for '{$id}' returned an invalid object.");
        }
    }
}
