<?php

declare(strict_types=1);

namespace app;

final class ModelFactory
{
    public function __construct(private ?Container $container = null)
    {
    }

    public function create(string $modelClass, ?Db $db = null): ActiveRecord
    {
        if ($this->container !== null) {
            $model = $this->container->get($modelClass);
            if (!$model instanceof ActiveRecord) {
                throw new \RuntimeException("Model factory expected '{$modelClass}' to extend ActiveRecord.");
            }
            return $model;
        }

        if ($db === null) {
            throw new \RuntimeException("Database dependency is required to create '{$modelClass}'.");
        }

        $model = new $modelClass($db);
        if (!$model instanceof ActiveRecord) {
            throw new \RuntimeException("Model factory expected '{$modelClass}' to extend ActiveRecord.");
        }
        return $model;
    }

    public function hydrate(string $modelClass, array $attributes, ?Db $db = null): ActiveRecord
    {
        $model = $this->create($modelClass, $db);
        $model->load($attributes);
        return $model;
    }
}
