<?php

declare(strict_types=1);

namespace app;

use PDO;
use RuntimeException;

final class Query
{
    private ?int $limitValue = null;
    private int $offsetValue = 0;
    private array $orderBy = [];
    private array $joins = [];
    private array $whereParts = [];
    private array $joinParams = [];
    private array $conditionParams = [];
    private int $parameterCounter = 0;
    private ?string $aliasValue = null;

    public ?bool $relMany = null;

    public function __construct(private string $modelClass, private Db $db, private ?ModelFactory $modelFactory = null)
    {
    }

    public function alias(string $alias): self { $this->aliasValue = $this->identifier($alias); return $this; }
    public function limit(int $limit): self { $this->limitValue = max(1, $limit); return $this; }
    public function offset(int $offset): self { $this->offsetValue = max(0, $offset); return $this; }

    public function orderBy(array $columns): self
    {
        foreach ($columns as $column => $direction) {
            $this->identifierPath((string) $column);
            $direction = strtoupper((string) $direction);
            if (!in_array($direction, ['ASC', 'DESC'], true)) throw new RuntimeException("Invalid order direction: {$direction}");
        }
        $this->orderBy = $columns;
        return $this;
    }

    public function asMany(): self { $this->relMany = true; return $this; }
    public function asOne(): self { $this->relMany = false; return $this; }

    public function join(string $type, string $table, string $on, array $params = []): self
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN'], true)) throw new RuntimeException("Unsupported JOIN type: {$type}");
        $this->joins[] = [$type, $table, $on];
        foreach ($params as $key => $value) $this->joinParams[':' . ltrim((string) $key, ':')] = $value;
        return $this;
    }

    public function leftJoin(string $table, string $on, array $params = []): self { return $this->join('LEFT JOIN', $table, $on, $params); }

    public function where(array|string|null $condition): self
    {
        $this->whereParts = [];
        $this->conditionParams = [];
        $this->parameterCounter = 0;
        return $this->andWhere($condition);
    }

    public function andWhere(array|string|null $condition): self { $sql = $this->buildCondition($condition); if ($sql !== '') $this->whereParts[] = ['AND', $sql]; return $this; }
    public function orWhere(array|string|null $condition): self { $sql = $this->buildCondition($condition); if ($sql !== '') $this->whereParts[] = ['OR', $sql]; return $this; }

    private function buildCondition(array|string|null $condition): string
    {
        if ($condition === null || $condition === [] || $condition === '') return '';
        if (is_string($condition)) throw new RuntimeException('Raw SQL WHERE conditions are not supported.');

        if ($this->isAssoc($condition)) {
            $parts = [];
            foreach ($condition as $column => $value) {
                $column = $this->identifierPath((string) $column);
                $parts[] = $value === null ? "{$column} IS NULL" : $column . ' = ' . $this->parameter($value);
            }
            return implode(' AND ', $parts);
        }

        $operator = strtolower((string) ($condition[0] ?? ''));
        if (in_array($operator, ['and', 'or'], true)) {
            $parts = [];
            for ($i = 1; $i < count($condition); $i++) {
                $part = $this->buildCondition($condition[$i]);
                if ($part !== '') $parts[] = '(' . $part . ')';
            }
            return implode(' ' . strtoupper($operator) . ' ', $parts);
        }

        $column = $this->identifierPath((string) ($condition[1] ?? ''));
        $value = $condition[2] ?? null;
        return match ($operator) {
            'like' => $column . ' LIKE ' . $this->parameter('%' . $value . '%'),
            'between' => $column . ' BETWEEN ' . $this->parameter($condition[2] ?? null) . ' AND ' . $this->parameter($condition[3] ?? null),
            'in' => $this->buildInCondition($column, $value),
            '=', '!=', '<>', '>', '>=', '<', '<=' => $value === null
                ? ($operator === '=' ? "{$column} IS NULL" : "{$column} IS NOT NULL")
                : $column . ' ' . $operator . ' ' . $this->parameter($value),
            default => throw new RuntimeException("Bad operator: {$operator}"),
        };
    }

    private function buildInCondition(string $column, mixed $values): string
    {
        if (!is_array($values) || $values === []) return '0=1';
        return $column . ' IN (' . implode(', ', array_map(fn ($value) => $this->parameter($value), $values)) . ')';
    }

    private function parameter(mixed $value): string { $name = ':p' . $this->parameterCounter++; $this->conditionParams[$name] = $value; return $name; }
    private function isAssoc(array $value): bool { return array_keys($value) !== range(0, count($value) - 1); }
    private function identifier(string $value): string { if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) throw new RuntimeException("Invalid SQL identifier: {$value}"); return '`' . $value . '`'; }
    private function identifierPath(string $value): string { $parts = explode('.', $value); if ($parts === [] || count($parts) > 2) throw new RuntimeException("Invalid SQL identifier path: {$value}"); return implode('.', array_map(fn ($part) => $this->identifier($part), $parts)); }

    public function one(): ?ActiveRecord { $this->limit(1); return $this->all()[0] ?? null; }

    public function count(): int
    {
        [$sql, $params] = $this->compile('COUNT(*) AS cnt');
        $statement = $this->db->pdo()->prepare($sql);
        $statement->execute($params);
        return (int) ($statement->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
    }

    public function all(): array
    {
        [$sql, $params] = $this->compile('*');
        $statement = $this->db->pdo()->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $factory = $this->modelFactory ?? $this->defaultModelFactory();

        return array_map(function (array $row) use ($factory): ActiveRecord {
            $instance = $factory->create($this->modelClass);
            $instance->load($row);
            return $instance;
        }, $rows);
    }

    private function defaultModelFactory(): ModelFactory
    {
        $container = new Container();
        $container->singleton(Db::class, $this->db);
        return new ModelFactory($container);
    }

    public function compileSql(string $select = '*'): array
    {
        $model = $this->modelClass;
        $sql = 'SELECT ' . $select . ' FROM ' . $this->identifierPath($model::tableName()) . ($this->aliasValue ? ' ' . $this->aliasValue : '');
        foreach ($this->joins as [$type, $table, $on]) $sql .= " {$type} {$table} ON {$on}";
        if ($this->whereParts !== []) {
            $chunks = [];
            foreach ($this->whereParts as $index => [$boolean, $part]) $chunks[] = $index === 0 ? $part : $boolean . ' ' . $part;
            $sql .= ' WHERE ' . implode(' ', $chunks);
        }
        if ($this->orderBy !== []) {
            $parts = [];
            foreach ($this->orderBy as $column => $direction) $parts[] = $this->identifierPath((string) $column) . ' ' . strtoupper((string) $direction);
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }
        if ($this->limitValue !== null) $sql .= ' LIMIT ' . $this->limitValue . ' OFFSET ' . $this->offsetValue;
        return [$sql, $this->joinParams + $this->conditionParams];
    }

    private function compile(string $select): array { return $this->compileSql($select); }
}
