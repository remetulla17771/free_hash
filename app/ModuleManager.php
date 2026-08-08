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
            $class = $config['class'] ?? null;
            if (!$class || !class_exists($class)) {
                throw new RuntimeException("Module class '{$class}' not found.");
            }

            $module = new $class($container, (string) $id, $config);
            if (!$module instanceof Module) {
                throw new RuntimeException("Module '{$id}' must extend app\\Module.");
            }

            $this->modules[$id] = $module;
            $container->singleton($class, $module);
            $container->singleton('module.' . $id, $module);
        }
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
}
