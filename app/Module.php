<?php

declare(strict_types=1);

namespace app;

use ReflectionClass;
use RuntimeException;

class Module
{
    private ?string $id = null;
    private array $config = [];

    public function __construct(protected Container $container)
    {
    }

    final public function configure(string $id, array $config = []): void
    {
        if ($id === '') {
            throw new RuntimeException('Module id cannot be empty.');
        }

        $this->id = $id;
        $this->config = $config;
        $this->init();
    }

    protected function init(): void
    {
    }

    final public function getId(): string
    {
        if ($this->id === null) {
            throw new RuntimeException('Module has not been configured.');
        }
        return $this->id;
    }

    final public function getConfig(?string $key = null): mixed
    {
        return $key === null ? $this->config : ($this->config[$key] ?? null);
    }

    public function getControllerNamespace(): string
    {
        $class = static::class;
        $position = strrpos($class, '\\');
        return $position === false ? 'controllers' : substr($class, 0, $position) . '\\controllers';
    }

    public function getViewPath(): string
    {
        $file = (new ReflectionClass(static::class))->getFileName();
        if ($file === false) {
            throw new RuntimeException('Unable to determine module source path.');
        }
        return dirname($file) . '/views';
    }

    protected function getContainer(): Container
    {
        return $this->container;
    }
}
