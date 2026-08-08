<?php

declare(strict_types=1);

namespace app;

use InvalidArgumentException;
use RuntimeException;

final class RecordPersister
{
    public function __construct(private Db $db)
    {
    }

    public function insert(string $table, array $attributes): int|string|null
    {
        if ($attributes === []) {
            throw new RuntimeException('Cannot insert an empty record.');
        }

        $columns = $placeholders = $params = [];
        foreach ($attributes as $key => $value) {
            $parameter = 'attr_' . count($params);
            $columns[] = $this->quoteIdentifier($key);
            $placeholders[] = ':' . $parameter;
            $params[$parameter] = $value;
        }

        $sql = 'INSERT INTO ' . $this->quoteIdentifier($table)
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')';

        $this->db->pdo()->prepare($sql)->execute($params);
        $id = $this->db->pdo()->lastInsertId();

        return $id === '0' ? null : $id;
    }

    public function update(string $table, mixed $id, array $attributes): bool
    {
        if ($attributes === []) {
            return true;
        }

        $sets = [];
        $params = ['id' => $id];
        foreach ($attributes as $key => $value) {
            $parameter = 'attr_' . count($params);
            $sets[] = $this->quoteIdentifier($key) . ' = :' . $parameter;
            $params[$parameter] = $value;
        }

        $sql = 'UPDATE ' . $this->quoteIdentifier($table)
            . ' SET ' . implode(', ', $sets)
            . ' WHERE `id` = :id';

        return $this->db->pdo()->prepare($sql)->execute($params);
    }

    public function delete(string $table, mixed $id): bool
    {
        $sql = 'DELETE FROM ' . $this->quoteIdentifier($table) . ' WHERE `id` = :id';
        return $this->db->pdo()->prepare($sql)->execute(['id' => $id]);
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/', $identifier)) {
            throw new InvalidArgumentException('Invalid SQL identifier: ' . $identifier);
        }

        return implode('.', array_map(
            static fn (string $part): string => '`' . $part . '`',
            explode('.', $identifier)
        ));
    }
}
