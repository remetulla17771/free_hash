<?php
declare(strict_types=1);

namespace app;

use PDO;

abstract class Migration
{

    protected function db(): PDO
    {
        return Db::getInstance();
    }

    protected function execute(string $sql): void
    {
        $res = $this->db()->exec($sql);
        if ($res === false) {
            $err = $this->db()->errorInfo();
            throw new \RuntimeException($err[2] ?? 'SQL error');
        }
    }

    // ---------- helpers ----------
    protected function createTable(string $table, array $columns, string $options = 'ENGINE=InnoDB DEFAULT CHARSET=utf8'): void
    {
        $this->assertName($table);

        $defs = [];
        foreach ($columns as $name => $type) {
            $this->assertName((string)$name);
            $defs[] = "`{$name}` {$type}";
        }

        $sql = "CREATE TABLE `{$table}` (\n  " . implode(",\n  ", $defs) . "\n) {$options}";
        $this->execute($sql);
    }

    protected function dropTable(string $table): void
    {
        $this->assertName($table);
        $this->execute("DROP TABLE IF EXISTS `{$table}`");
    }

    protected function addColumn(string $table, string $column, string $type): void
    {
        $this->assertName($table);
        $this->assertName($column);
        $this->execute("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$type}");
    }

    protected function dropColumn(string $table, string $column): void
    {
        $this->assertName($table);
        $this->assertName($column);
        $this->execute("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    }

    protected function createIndex(string $name, string $table, $columns, bool $unique = false): void
    {
        $this->assertName($name);
        $this->assertName($table);

        $cols = is_array($columns) ? $columns : [$columns];
        $parts = [];
        foreach ($cols as $c) {
            $this->assertName((string)$c);
            $parts[] = "`{$c}`";
        }

        $u = $unique ? "UNIQUE " : "";
        $sql = "CREATE {$u}INDEX `{$name}` ON `{$table}` (" . implode(", ", $parts) . ")";
        $this->execute($sql);
    }

    protected function dropIndex(string $name, string $table): void
    {
        $this->assertName($name);
        $this->assertName($table);
        $this->execute("DROP INDEX `{$name}` ON `{$table}`");
    }

    // ---------- type helpers ----------
    protected function pk(): string { return "INT NOT NULL AUTO_INCREMENT PRIMARY KEY"; }
    protected function int(): string { return "INT"; }
    protected function bool(): string { return "TINYINT(1)"; }
    protected function string(int $len = 255): string { return "VARCHAR({$len})"; }
    protected function text(): string { return "TEXT"; }
    protected function datetime(): string { return "DATETIME"; }
    protected function timestamp(): string { return "TIMESTAMP"; }
    protected function decimal(int $p = 10, int $s = 0): string { return "DECIMAL({$p},{$s})"; }

    protected function notNull(): string { return "NOT NULL"; }
    protected function defaultValue($v): string { return "DEFAULT " . $this->quote($v); }
    protected function defaultExpr(string $expr): string { return "DEFAULT {$expr}"; } // CURRENT_TIMESTAMP и т.п.

    private function quote($v): string
    {
        if ($v === null) return "NULL";
        if (is_bool($v)) return $v ? "1" : "0";
        if (is_int($v) || is_float($v)) return (string)$v;

        // строка
        $s = (string)$v;
        $s = str_replace("'", "''", $s);
        return "'{$s}'";
    }

    private function assertName(string $name): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \RuntimeException("Bad identifier: {$name}");
        }
    }

    // ---------- required ----------
    abstract public function up(): void;
    abstract public function down(): void;


}
