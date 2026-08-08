<?php

declare(strict_types=1);

namespace app;

use PDO;
use RuntimeException;

final class Db
{
    public function __construct(private PDO $pdo)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this->pdo);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public static function fromConfig(array $config): self
    {
        foreach (['dsn', 'user', 'password'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new RuntimeException("Database config is missing '{$key}'.");
            }
        }

        $pdo = new PDO(
            (string) $config['dsn'],
            (string) $config['user'],
            (string) $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return new self($pdo);
    }
}
