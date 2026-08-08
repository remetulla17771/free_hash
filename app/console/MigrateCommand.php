<?php

declare(strict_types=1);

namespace app\console;

use app\Container;
use app\Db;
use RuntimeException;

final class MigrateCommand implements CommandInterface
{
    public function __construct(private Db $db, private Container $container)
    {
    }

    public function name(): string { return 'migrate'; }
    public function description(): string { return 'Apply or rollback migrations'; }

    public function execute(Input $in, Output $out): int
    {
        $action = strtolower((string) $in->arg(0, 'up'));
        $count = max(1, (int) $in->arg(1, 1));
        if (!in_array($action, ['up', 'down'], true)) {
            $out->err('Usage: php console migrate [up|down] [count]');
            return 1;
        }

        $root = dirname(__DIR__, 2);
        $dir = (string) $in->opt('dir', 'migrations');
        $table = (string) $in->opt('table', 'migration');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            throw new RuntimeException("Bad migration table name: {$table}");
        }

        $dirPath = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
        $pdo = $this->db->pdo();
        $this->ensureMigrationTable($pdo, $table);

        return $action === 'down'
            ? $this->down($pdo, $table, $dirPath, $count, $out)
            : $this->up($pdo, $table, $dirPath, $out);
    }

    private function ensureMigrationTable(\PDO $pdo, string $table): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}` (version VARCHAR(180) NOT NULL PRIMARY KEY, apply_time INT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    private function up(\PDO $pdo, string $table, string $dirPath, Output $out): int
    {
        $files = $this->scanMigrations($dirPath);
        $applied = array_fill_keys($this->getApplied($pdo, $table), true);
        $pending = array_diff_key($files, $applied);
        if ($pending === []) {
            $out->line('No pending migrations.');
            return 0;
        }

        foreach ($pending as $version => $file) {
            $out->line("Applying: {$version}");
            require_once $file;
            if (!class_exists($version)) {
                $out->err("Class not found: {$version}");
                return 1;
            }

            try {
                $migration = $this->container->build($version);
                if (!$migration instanceof \app\Migration) {
                    throw new RuntimeException("Migration {$version} must extend app\\Migration.");
                }
                $this->db->transaction(function () use ($migration, $pdo, $table, $version): void {
                    $migration->up();
                    $stmt = $pdo->prepare("INSERT INTO `{$table}` (version, apply_time) VALUES (:version, :time)");
                    $stmt->execute(['version' => $version, 'time' => time()]);
                });
                $out->line("OK: {$version}");
            } catch (\Throwable $e) {
                $out->err("FAILED: {$version}");
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
        if ($applied === []) {
            $out->line('Nothing to rollback.');
            return 0;
        }

        foreach (array_reverse(array_slice($applied, -$count)) as $version) {
            if (!isset($files[$version])) {
                $out->err("Migration file not found for: {$version}");
                return 1;
            }
            require_once $files[$version];
            if (!class_exists($version)) {
                $out->err("Class not found: {$version}");
                return 1;
            }

            $out->line("Rolling back: {$version}");
            try {
                $migration = $this->container->build($version);
                if (!$migration instanceof \app\Migration) {
                    throw new RuntimeException("Migration {$version} must extend app\\Migration.");
                }
                $this->db->transaction(function () use ($migration, $pdo, $table, $version): void {
                    $migration->down();
                    $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE version = :version");
                    $stmt->execute(['version' => $version]);
                });
                $out->line("OK: {$version}");
            } catch (\Throwable $e) {
                $out->err("FAILED: {$version}");
                $out->err($e->getMessage());
                return 1;
            }
        }
        return 0;
    }

    private function getApplied(\PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SELECT version FROM `{$table}` ORDER BY apply_time ASC, version ASC");
        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @return array<string,string> */
    private function scanMigrations(string $dirPath): array
    {
        if (!is_dir($dirPath)) return [];
        $files = glob($dirPath . DIRECTORY_SEPARATOR . 'm*.php') ?: [];
        sort($files, SORT_STRING);
        $result = [];
        foreach ($files as $file) {
            $version = basename($file, '.php');
            if (preg_match('/^m\d{6}_\d{6}_[a-z0-9_]+$/', $version)) $result[$version] = $file;
        }
        return $result;
    }
}
