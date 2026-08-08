<?php

declare(strict_types=1);

namespace app;

final class QueryFactory
{
    public function __construct(
        private ModelFactory $modelFactory,
        private QueryExecutor $executor,
    ) {
    }

    public function create(string $modelClass, Db $db): Query
    {
        return new Query($modelClass, $db, $this->modelFactory, $this->executor);
    }
}
