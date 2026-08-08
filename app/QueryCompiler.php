<?php

declare(strict_types=1);

namespace app;

final class QueryCompiler
{
    public function compile(Query $query, string $select): array
    {
        return $query->compileSql($select);
    }
}
