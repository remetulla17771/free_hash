<?php

declare(strict_types=1);

namespace app;

use PDO;
use RuntimeException;

final class Db
{
    private static ?self $instance = null;

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
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
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
            if (!array_key_exists($key, $config)) throw new RuntimeException("Database config is missing '{$key}'.");
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

        return self::$instance = new self($pdo);
    }

    /**
     * Legacy compatibility for generators that have not yet been converted to constructor DI.
     * New application code should inject Db instead.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database is not initialized. Create Db from configuration first.');
        }
        return self::$instance->pdo();
    }
}
