<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

final class ModuleManager
{
    /** @var array<string, Module> */
    private array $modules = [];

    public function __construct(private Container $container, array $definitions = [])
    {
        foreach ($definitions as $id => $config) {
            $this->register((string) $id, $config);
        }
    }

    public function register(string $id, array $config): Module
    {
        if ($id === '') {
            throw new RuntimeException('Module id cannot be empty.');
        }

        if (isset($this->modules[$id])) {
            throw new RuntimeException("Module '{$id}' is already registered.");
        }

        $class = $config['class'] ?? Module::class;
        if (!is_string($class) || !class_exists($class)) {
            throw new RuntimeException("Module class '{$class}' not found.");
        }

        if (!is_a($class, Module::class, true)) {
            throw new RuntimeException("Module '{$id}' must extend " . Module::class . '.');
        }

        $module = $this->container->build($class);

        // Module receives framework context explicitly. Keep this assignment
        // compatible with custom modules that rely on the base constructor.
        $module = new $class($this->container, $id, $config);

        $this->modules[$id] = $module;
        $this->container->singleton($class, $module);
        $this->container->singleton('module.' . $id, $module);

        return $module;
    }

    public function has(string $id): bool
    {
        return isset($this->modules[$id]);
    }

    public function get(string $id): Module
    {
        if (!$this->has($id)) {
            throw new RuntimeException("Module '{$id}' is not registered.", 404);
        }

        return $this->modules[$id];
    }

    /** @return array<string, Module> */
    public function all(): array
    {
        return $this->modules;
    }
}
