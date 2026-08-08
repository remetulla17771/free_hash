<?php

declare(strict_types=1);

namespace app;

final class ModelFactory
{
    public function __construct(private Container $container)
    {
    }

    public function create(string $modelClass): object
    {
        return $this->container->get($modelClass);
    }
}
