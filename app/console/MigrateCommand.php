<?php
declare(strict_types=1);

namespace app\console;

use app\Db;

class MigrateCommand implements CommandInterface
{
    public function name(): string { return 'migrate'; }
    public function description(): string { return 'Apply/rollback migrations'; }

    public function execute(Input $in, Output $out): int
    {
        $action = (string)$in->arg(0, 'up'); // up|down
        $count  = (int)$in->arg(1, 1);

        $dir   = (string)$in->opt('dir', 'migrations');
        $table = (string)$in->opt('table', 'migration');

        $root = dirname(__DIR__, 2);
        $dirPath = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);

        $pdo = Db::getInstance();
        $this->ensureMigrationTable($pdo, $table);

        if ($action === 'down') {
            return $this->down($pdo, $table, $dirPath, $count, $out);
        }

        // default: up
        return $this->up($pdo, $table, $dirPath, $out);
    }

    private function ensureMigrationTable(\PDO $pdo, string $table): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            throw new \RuntimeException("Bad migration table name: $table");
        }

        $sql =
            "CREATE TABLE IF NOT EXISTS `$table` (" .
            "  version VARCHAR(180) NOT NULL PRIMARY KEY," .
            "  apply_time INT NULL" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $pdo->exec($sql);
    }

    private function up(\PDO $pdo, string $table, string $dirPath, Output $out): int
    {
        $files = $this->scanMigrations($dirPath);
        $applied = $this->getApplied($pdo, $table);
        $appliedSet = array_fill_keys($applied, true);

        $pending = [];
        foreach ($files as $version => $file) {
            if (!isset($appliedSet[$version])) $pending[$version] = $file;
        }

        if (!$pending) {
            $out->line("No pending migrations.");
            return 0;
        }

        foreach ($pending as $version => $file) {
            $out->line("Applying: $version");

            require_once $file;

            if (!class_exists($version)) {
                $out->err("Class not found: $version in $file");
                return 1;
            }

            $m = new $version();

            try {
                $m->up();
                $stmt = $pdo->prepare("INSERT INTO `$table` (version, apply_time) VALUES (:v, :t)");
                $stmt->execute(['v' => $version, 't' => time()]);
                $out->line("OK: $version");
            } catch (\Throwable $e) {
                $out->err("FAILED: $version");
                $out->err($e->getMessage());
                return 1;
            }
        }

        return 0;
    }

    private function down(\PDO $pdo, string $table, string $dirPath, int $count, Output $out): int
    {
        $files = $this->scanMigrations($dirPath);
        $applied = $this->getApplied($pdo, $table);

        if (!$applied) {
            $out->line("Nothing to rollback.");
            return 0;
        }

        $count = max(1, $count);
        $toRollback = array_slice($applied, -$count);
        $toRollback = array_reverse($toRollback);

        foreach ($toRollback as $version) {
            if (!isset($files[$version])) {
                $out->err("Migration file not found for: $version");
                return 1;
            }

            $file = $files[$version];
            $out->line("Rolling back: $version");

            require_once $file;

            if (!class_exists($version)) {
                $out->err("Class not found: $version in $file");
                return 1;
            }

            $m = new $version();

            try {
                $m->down();
                $stmt = $pdo->prepare("DELETE FROM `$table` WHERE version = :v");
                $stmt->execute(['v' => $version]);
                $out->line("OK: $version");
            } catch (\Throwable $e) {
                $out->err("FAILED: $version");
                $out->err($e->getMessage());
                return 1;
            }
        }

        return 0;
    }

    private function getApplied(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SELECT version FROM `$table` ORDER BY apply_time ASC, version ASC");
        $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
        $out = [];
        foreach ($rows as $v) $out[] = (string)$v;
        return $out;
    }

    /** @return array<string,string> version => filepath */
    private function scanMigrations(string $dirPath): array
    {
        if (!is_dir($dirPath)) return [];

        $list = glob($dirPath . DIRECTORY_SEPARATOR . 'm*.php');
        if (!$list) return [];

        sort($list);

        $out = [];
        foreach ($list as $file) {
            $base = basename($file, '.php');
            // минимальная проверка формата имени
            if (preg_match('/^m\\d{6}_\\d{6}_[a-z0-9_]+$/', $base)) {
                $out[$base] = $file;
            }
        }
        return $out;
    }
}
