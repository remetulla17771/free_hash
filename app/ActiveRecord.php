<?php

namespace app;

abstract class ActiveRecord
{
    protected array $attributes = [];
    protected array $_relCache = [];

    abstract public static function tableName(): string;

//    public function __get($name)
//    {
//        return $this->attributes[$name] ?? null;
//    }

    public function __get($name)
    {
        // relation cache
        if (array_key_exists($name, $this->_relCache)) return $this->_relCache[$name];

        // relations: getX()
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            $q = $this->$method();
            if ($q instanceof \app\Query) {
                $many = $q->relMany ?? $this->isPluralName($name);
                return $this->_relCache[$name] = ($many ? $q->all() : $q->one());
            }
            return $this->_relCache[$name] = $q;
        }

        // обычные атрибуты (id, login, ...)
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __isset($name): bool
    {
        return isset($this->attributes[$name]) || method_exists($this, 'get' . ucfirst($name));
    }
    protected function isPluralName(string $name): bool
    {
        $n = strtolower($name);
        return str_ends_with($n, 's') || str_ends_with($n, 'list') || str_ends_with($n, 'items');
    }

    public function load(array $data, ?string $formName = null)
    {
//        if ($formName === null) {
//            $formName = (new \ReflectionClass($this))->getShortName();
//        }
//
//        if (isset($data[$formName]) && is_array($data[$formName])) {
//            $data = $data[$formName];
//        }
//
//        $loaded = false;

        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return true;


    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function hasOne(string $class, array $link): Query
    {
        $fk = array_key_first($link);
        $pk = $link[$fk];

        if (!array_key_exists($pk, $this->attributes)) {
            throw new \RuntimeException("Relation key '{$pk}' is not loaded");
        }

        return $class::find()->where([$fk => $this->attributes[$pk]])->asOne();
    }

    public function hasMany(string $class, array $link): Query
    {
        $fk = array_key_first($link);
        $pk = $link[$fk];

        if (!array_key_exists($pk, $this->attributes)) {
            throw new \RuntimeException("Relation key '{$pk}' is not loaded");
        }

        return $class::find()->where([$fk => $this->attributes[$pk]])->asMany();
    }




    public static function find()
    {
        return new Query(static::class);
    }

    public static function findOne(int $id)
    {
        return static::find()
            ->where(['id' => $id])
            ->one();
    }

    public function delete()
    {
        if (!isset($this->attributes['id'])) {
            throw new \Exception('Невозможно удалить: id не задан');
        }

        $db = Db::getInstance();
        $sql = "DELETE FROM " . static::tableName() . " WHERE id = :id";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            'id' => $this->attributes['id']
        ]);
    }


    public static function deleteAll(array $condition = [])
    {
        $sql = "DELETE FROM " . static::tableName();
        $params = [];

        if (!empty($condition)) {
            $parts = [];
            foreach ($condition as $k => $v) {
                $parts[] = "$k = :$k";
                $params[$k] = $v;
            }
            $sql .= " WHERE " . implode(' AND ', $parts);
        }

        $stmt = Db::pdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public function getPrimaryKey(string $name = 'id')
    {
        return $this->attributes[$name] ?? null;
    }

    public function save(): bool
    {
        $db = Db::getInstance();

        if (isset($this->attributes['id'])) {
            // update
            $fields = [];
            foreach ($this->attributes as $key => $value) {
                if ($key === 'id') continue;
                $fields[] = "$key = :$key";
            }

            $sql = "UPDATE " . static::tableName()
                . " SET " . implode(', ', $fields)
                . " WHERE id = :id";


        } else {
            // insert
            $keys = array_keys($this->attributes);
            $columns = implode(',', $keys);
            $params  = ':' . implode(',:', $keys);

            $sql = "INSERT INTO " . static::tableName()
                . " ($columns) VALUES ($params)";
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($this->attributes);
    }
}
