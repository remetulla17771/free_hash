<?php

declare(strict_types=1);

namespace app;

use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

abstract class ActiveRecord implements JsonSerializable
{
    protected array $attributes = [];
    protected array $_relCache = [];

    abstract public static function tableName(): string;
    abstract public function attributeLabels();

    public function __construct(protected ?Db $db = null, protected ?RecordPersister $persister = null)
    {
    }

    protected function database(): Db
    {
        if ($this->db === null) {
            throw new RuntimeException('Database dependency is not configured for model ' . static::class . '.');
        }
        return $this->db;
    }

    protected function recordPersister(): RecordPersister
    {
        if ($this->persister !== null) {
            return $this->persister;
        }
        return new RecordPersister($this->database());
    }

    public function jsonSerialize(): mixed { return $this->toArray(); }
    public function toArray(): array { return $this->attributes; }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_relCache)) return $this->_relCache[$name];
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            $query = $this->$method();
            if ($query instanceof Query) {
                $many = $query->relMany ?? $this->isPluralName($name);
                return $this->_relCache[$name] = $many ? $query->all() : $query->one();
            }
            return $this->_relCache[$name] = $query;
        }
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value): void
    {
        $this->attributes[$name] = $value;
        unset($this->_relCache[$name]);
    }

    public function __isset($name): bool { return isset($this->attributes[$name]) || method_exists($this, 'get' . ucfirst($name)); }

    protected function isPluralName(string $name): bool
    {
        $name = strtolower($name);
        return str_ends_with($name, 's') || str_ends_with($name, 'list') || str_ends_with($name, 'items');
    }

    public function load(array $data, ?string $formName = null): bool
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $key !== '') $this->attributes[$key] = $value;
        }
        $this->_relCache = [];
        return true;
    }

    public function hasAttribute(string $name): bool { return array_key_exists($name, $this->attributes); }

    public function hasOne(string $class, array $link): Query
    {
        [$foreignKey, $primaryKey] = $this->relationLink($link);
        return $class::find($this->database())->where([$foreignKey => $this->requiredAttribute($primaryKey)])->asOne();
    }

    public function hasMany(string $class, array $link): Query
    {
        [$foreignKey, $primaryKey] = $this->relationLink($link);
        return $class::find($this->database())->where([$foreignKey => $this->requiredAttribute($primaryKey)])->asMany();
    }

    private function relationLink(array $link): array
    {
        if (count($link) !== 1) throw new InvalidArgumentException('Relation link must contain exactly one foreign/primary key pair.');
        $foreignKey = array_key_first($link);
        $primaryKey = $link[$foreignKey];
        if (!is_string($foreignKey) || !is_string($primaryKey) || $foreignKey === '' || $primaryKey === '') throw new InvalidArgumentException('Relation keys must be non-empty strings.');
        return [$foreignKey, $primaryKey];
    }

    private function requiredAttribute(string $name): mixed
    {
        if (!$this->hasAttribute($name)) throw new RuntimeException("Relation key '{$name}' is not loaded.");
        return $this->attributes[$name];
    }

    public static function find(Db $db): Query { return new Query(static::class, $db); }
    public static function findOne(int $id, Db $db): ?static { return static::find($db)->where(['id' => $id])->one(); }

    public function delete(): bool
    {
        return $this->recordPersister()->delete(static::tableName(), $this->requiredAttribute('id'));
    }

    public static function deleteAll(array $condition, Db $db): bool
    {
        [$where, $params] = self::buildCondition($condition);
        $sql = 'DELETE FROM ' . self::quoteIdentifier(static::tableName()) . ($where !== '' ? ' WHERE ' . $where : '');
        return $db->pdo()->prepare($sql)->execute($params);
    }

    public function getPrimaryKey(string $name = 'id'): mixed { return $this->attributes[$name] ?? null; }

    public function save(): bool
    {
        if ($this->attributes === []) throw new RuntimeException('Cannot save an empty model.');
        $attributes = $this->attributes;
        $id = $attributes['id'] ?? null;
        unset($attributes['id']);

        if ($id !== null) {
            return $this->recordPersister()->update(static::tableName(), $id, $attributes);
        }

        $newId = $this->recordPersister()->insert(static::tableName(), $attributes);
        if ($newId !== null && !array_key_exists('id', $this->attributes)) $this->attributes['id'] = (int) $newId;
        return true;
    }

    private static function buildCondition(array $condition): array
    {
        $parts = $params = [];
        foreach ($condition as $column => $value) {
            if (!is_string($column) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column)) throw new InvalidArgumentException('Invalid SQL column name.');
            $parameter = 'condition_' . count($params);
            $parts[] = self::quoteIdentifier($column) . ' = :' . $parameter;
            $params[$parameter] = $value;
        }
        return [implode(' AND ', $parts), $params];
    }

    private static function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?$/', $identifier)) throw new InvalidArgumentException('Invalid SQL identifier: ' . $identifier);
        return implode('.', array_map(static fn (string $part) => '`' . $part . '`', explode('.', $identifier)));
    }
}
