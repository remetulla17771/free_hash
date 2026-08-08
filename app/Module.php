<?php

declare(strict_types=1);

namespace app;

class Module
{
    public function __construct(
        protected Container $container,
        protected string $id,
        protected array $config = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getConfig(?string $key = null): mixed
    {
        return $key === null ? $this->config : ($this->config[$key] ?? null);
    }

    public function getControllerNamespace(): string
    {
        $class = static::class;
        $namespace = substr($class, 0, (int) strrpos($class, '\\'));
        return $namespace . '\\controllers';
    }

    public function getViewPath(): string
    {
        return dirname((new \ReflectionClass(static::class))->getFileName()) . '/views';
    }
}
