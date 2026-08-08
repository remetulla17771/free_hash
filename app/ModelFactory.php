<?php

declare(strict_types=1);

namespace app;

final class ModelFactory
{
    public function __construct(private Container $container)
    {
    }

    public function create(string $modelClass): ActiveRecord
    {
        $model = $this->container->get($modelClass);
        if (!$model instanceof ActiveRecord) {
            throw new \RuntimeException("Model factory expected '{$modelClass}' to extend ActiveRecord.");
        }
        return $model;
    }

    public function hydrate(string $modelClass, array $attributes): ActiveRecord
    {
        $model = $this->create($modelClass);
        $model->load($attributes);
        return $model;
    }
}
