<?php

namespace app;

class Query
{
    private string $modelClass;

    private ?int $limit = null;
    private int $offset = 0;
    private array $orderBy = []; // ['id' => 'DESC']

    private array $joins = [];        // [[type, table, on], ...]
    private array $whereParts = [];   // [['AND'|'OR', '<sql>'], ...]

    // params are split to avoid "Invalid parameter number" when you reset WHERE
    private array $joinParams = [];
    private array $condParams = [];
    private int $paramCounter = 0;

    private ?string $alias = null;

    public ?bool $relMany = null;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /* =========================
     * Fluent helpers
     * ========================= */

    public function alias(string $alias): self
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias)) {
            throw new \RuntimeException("Bad alias: $alias");
        }
        $this->alias = $alias;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(1, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    public function orderBy(array $columns): self
    {
        $this->orderBy = $columns;
        return $this;
    }

    public function asMany(): self
    {
        $this->relMany = true;
        return $this;
    }

    public function asOne(): self
    {
        $this->relMany = false;
        return $this;
    }

    /* =========================
     * JOIN
     * ========================= */

    public function join(string $type, string $table, string $on, array $params = []): self
    {
        // NOTE: $table and $on are treated as trusted SQL snippets.
        $this->joins[] = [$type, $table, $on];

        foreach ($params as $k => $v) {
            $k = (string)$k;
            if ($k === '') continue;
            if ($k[0] !== ':') $k = ':' . $k;
            $this->joinParams[$k] = $v;
        }
        return $this;
    }

    public function leftJoin(string $table, string $on, array $params = []): self
    {
        return $this->join('LEFT JOIN', $table, $on, $params);
    }

    /* =========================
     * WHERE
     * ========================= */

    public function where($condition): self
    {
        $this->whereParts = [];
        $this->condParams = [];
        $this->paramCounter = 0;
        return $this->andWhere($condition);
    }

    public function andWhere($condition): self
    {
        $sql = $this->buildCondition($condition);
        if ($sql !== '') $this->whereParts[] = ['AND', $sql];
        return $this;
    }

    public function orWhere($condition): self
    {
        $sql = $this->buildCondition($condition);
        if ($sql !== '') $this->whereParts[] = ['OR', $sql];
        return $this;
    }

    private function buildCondition($condition): string
    {
        if ($condition === null || $condition === [] || $condition === '') return '';

        // where(['id'=>1, 'status'=>2]) or with alias: ['u.id'=>1]
        if (is_array($condition) && $this->isAssoc($condition)) {
            $parts = [];
            foreach ($condition as $col => $val) {
                $colSql = $this->quoteColumn((string)$col);

                if ($val === null) {
                    $parts[] = "{$colSql} IS NULL";
                    continue;
                }

                $ph = $this->nextParam($val);
                $parts[] = "{$colSql} = {$ph}";
            }
            return implode(' AND ', $parts);
        }

        // operator format: ['like','col','x']
        if (is_array($condition)) {
            $op = strtolower((string)($condition[0] ?? ''));

            if ($op === 'and' || $op === 'or') {
                $glue = strtoupper($op);
                $sub = [];
                for ($i = 1; $i < count($condition); $i++) {
                    $s = $this->buildCondition($condition[$i]);
                    if ($s !== '') $sub[] = "({$s})";
                }
                return implode(" {$glue} ", $sub);
            }

            if ($op === 'like') {
                $col = $this->quoteColumn((string)$condition[1]);
                $val = $condition[2] ?? '';
                $ph = $this->nextParam('%' . $val . '%');
                return "{$col} LIKE {$ph}";
            }

            if ($op === 'between') {
                $col = $this->quoteColumn((string)$condition[1]);
                $a = $condition[2] ?? null;
                $b = $condition[3] ?? null;
                $ph1 = $this->nextParam($a);
                $ph2 = $this->nextParam($b);
                return "{$col} BETWEEN {$ph1} AND {$ph2}";
            }

            if ($op === 'in') {
                $col = $this->quoteColumn((string)$condition[1]);
                $vals = $condition[2] ?? [];
                if (!is_array($vals) || !$vals) return '0=1';

                $phs = [];
                foreach ($vals as $v) $phs[] = $this->nextParam($v);
                return "{$col} IN (" . implode(',', $phs) . ")";
            }

            // default binary: ['=','col',val], ['>','col',val] ...
            $allowedOps = ['=', '!=', '<>', '>', '>=', '<', '<='];
            $rawOp = (string)$condition[0];
            if (!in_array($rawOp, $allowedOps, true)) {
                throw new \RuntimeException("Bad operator: {$rawOp}");
            }

            $col = $this->quoteColumn((string)$condition[1]);
            $val = $condition[2] ?? null;

            if ($val === null) return "{$col} IS NULL";

            $ph = $this->nextParam($val);
            return "{$col} {$rawOp} {$ph}";
        }

        throw new \RuntimeException('Bad where condition');
    }

    private function nextParam($value): string
    {
        $name = ':p' . $this->paramCounter++;
        $this->condParams[$name] = $value;
        return $name;
    }

    private function isAssoc(array $a): bool
    {
        return array_keys($a) !== range(0, count($a) - 1);
    }

    private function quoteColumn(string $name): string
    {
        // allow u.id
        if (!preg_match('/^[A-Za-z0-9_\.]+$/', $name)) {
            throw new \RuntimeException("Bad column: {$name}");
        }
        $parts = explode('.', $name);
        $parts = array_map(fn($p) => "`{$p}`", $parts);
        return implode('.', $parts);
    }

    /* =========================
     * Execution
     * ========================= */

    public function one()
    {
        $this->limit(1);
        $results = $this->all();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $model = $this->modelClass;
        $table = $model::tableName();

        $sql = "SELECT COUNT(*) AS cnt FROM {$table}" . ($this->alias ? " {$this->alias}" : "");

        if (!empty($this->joins)) {
            foreach ($this->joins as [$type, $joinTable, $on]) {
                $sql .= " {$type} {$joinTable} ON {$on}";
            }
        }

        if (!empty($this->whereParts)) {
            $chunks = [];
            foreach ($this->whereParts as $i => [$bool, $piece]) {
                $chunks[] = ($i === 0) ? $piece : "{$bool} {$piece}";
            }
            $sql .= " WHERE " . implode(' ', $chunks);
        }

        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute($this->allParams());

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    public function all(): array
    {
        $model = $this->modelClass;
        $table = $model::tableName();

        // 1) SELECT + FROM (+ alias)
        $sql = "SELECT * FROM {$table}" . ($this->alias ? " {$this->alias}" : "");

        // 2) JOIN
        if (!empty($this->joins)) {
            foreach ($this->joins as [$type, $joinTable, $on]) {
                $sql .= " {$type} {$joinTable} ON {$on}";
            }
        }

        // 3) WHERE
        if (!empty($this->whereParts)) {
            $chunks = [];
            foreach ($this->whereParts as $i => [$bool, $piece]) {
                $chunks[] = ($i === 0) ? $piece : "{$bool} {$piece}";
            }
            $sql .= " WHERE " . implode(' ', $chunks);
        }

        // 4) ORDER BY (supports u.id)
        if (!empty($this->orderBy)) {
            $parts = [];
            foreach ($this->orderBy as $col => $dir) {
                $colSql = $this->quoteColumn((string)$col);
                $dir = strtoupper((string)$dir);
                $dir = ($dir === 'DESC') ? 'DESC' : 'ASC';
                $parts[] = "{$colSql} {$dir}";
            }
            $sql .= " ORDER BY " . implode(', ', $parts);
        }

        // 5) LIMIT/OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT " . (int)$this->limit . " OFFSET " . (int)$this->offset;
        }

        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute($this->allParams());

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function (array $row) use ($model) {
            $obj = new $model();
            foreach ($row as $k => $v) {
                $obj->$k = $v; // goes to ActiveRecord::__set()
            }
            return $obj;
        }, $rows);
    }

    private function allParams(): array
    {
        // join params + condition params
        // if same key repeats, condition param wins
        return $this->joinParams + $this->condParams;
    }
}
