<?php

declare(strict_types=1);

namespace app;

use PDO;

final class QueryExecutor
{
    public function __construct(private PDO $pdo)
    {
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function execute(string $sql, array $params = []): bool
    {
        return $this->pdo->prepare($sql)->execute($params);
    }
}
