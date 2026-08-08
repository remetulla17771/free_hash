<?php

declare(strict_types=1);

namespace app;

use PDO;

abstract class Migration
{
    public function __construct(protected Db $database)
    {
    }

    protected function db(): PDO
    {
        return $this->database->pdo();
    }

    protected function execute(string $sql): void
    {
        $this->db()->exec($sql);
    }

    protected function createTable(string $table, array $columns, string $options = 'ENGINE=InnoDB DEFAULT CHARSET=utf8'): void
    {
        $this->assertName($table);
        $defs = [];
        foreach ($columns as $name => $type) {
            $this->assertName((string) $name);
            $defs[] = "`{$name}` {$type}";
        }
        $this->execute("CREATE TABLE `{$table}` (\n  " . implode(",\n  ", $defs) . "\n) {$options}");
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

    protected function createIndex(string $name, string $table, array|string $columns, bool $unique = false): void
    {
        $this->assertName($name);
        $this->assertName($table);
        $cols = is_array($columns) ? $columns : [$columns];
        $parts = [];
        foreach ($cols as $column) {
            $this->assertName((string) $column);
            $parts[] = '`' . $column . '`';
        }
        $uniqueSql = $unique ? 'UNIQUE ' : '';
        $this->execute("CREATE {$uniqueSql}INDEX `{$name}` ON `{$table}` (" . implode(', ', $parts) . ')');
    }

    protected function dropIndex(string $name, string $table): void
    {
        $this->assertName($name);
        $this->assertName($table);
        $this->execute("DROP INDEX `{$name}` ON `{$table}`");
    }

    protected function pk(): string { return 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY'; }
    protected function int(): string { return 'INT'; }
    protected function bool(): string { return 'TINYINT(1)'; }
    protected function string(int $len = 255): string { return "VARCHAR({$len})"; }
    protected function text(): string { return 'TEXT'; }
    protected function datetime(): string { return 'DATETIME'; }
    protected function timestamp(): string { return 'TIMESTAMP'; }
    protected function decimal(int $p = 10, int $s = 0): string { return "DECIMAL({$p},{$s})"; }
    protected function notNull(): string { return 'NOT NULL'; }
    protected function defaultValue(mixed $value): string { return 'DEFAULT ' . $this->quote($value); }
    protected function defaultExpr(string $expr): string { return "DEFAULT {$expr}"; }

    private function quote(mixed $value): string
    {
        if ($value === null) return 'NULL';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_int($value) || is_float($value)) return (string) $value;
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function assertName(string $name): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new \RuntimeException("Bad identifier: {$name}");
        }
    }

    abstract public function up(): void;
    abstract public function down(): void;
}
