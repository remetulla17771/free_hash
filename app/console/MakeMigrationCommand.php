<?php
declare(strict_types=1);

namespace app\console;

use RuntimeException;

class MakeMigrationCommand implements CommandInterface
{
    public function name(): string { return 'make:migration'; }
    public function description(): string { return 'Generate migration file'; }

    public function execute(Input $in, Output $out): int
    {
        $rawName = trim((string)$in->arg(0, ''));
        if ($rawName === '') {
            $out->line('Usage: php bin/console.php make:migration create_users_table [--dir=migrations] [--force]');
            return 1;
        }

        $name = $this->toSnake($rawName);
        if ($name === '' || !preg_match('/^[a-z0-9_]+$/', $name)) {
            $out->err("Bad migration name: {$rawName}");
            return 1;
        }

        $dir = trim((string)$in->opt('dir', 'migrations'));
        $force = $in->has('force');
        if ($dir === '') {
            $dir = 'migrations';
        }

        $root = dirname(__DIR__, 2);
        $dirPath = $this->resolveDirectory($root, $dir);
        if (!is_dir($dirPath) && !mkdir($dirPath, 0777, true) && !is_dir($dirPath)) {
            throw new RuntimeException("Cannot create migration directory: {$dirPath}");
        }

        $class = 'm' . date('ymd_His') . '_' . $name;
        $file = $dirPath . DIRECTORY_SEPARATOR . $class . '.php';
        $code = $this->buildMigrationCode($class, $name);

        $this->writeFile($file, $code, $force);

        $out->line("OK: {$file}");
        return 0;
    }

    private function buildMigrationCode(string $class, string $name): string
    {
        $operation = $this->parseOperation($name);

        if ($operation['type'] === 'create') {
            $up =
                "        \$this->createTable('{$operation['table']}', [\n" .
                "            'id' => \$this->pk(),\n" .
                "            'created_at' => \$this->timestamp() . ' ' . \$this->notNull() . ' ' . \$this->defaultExpr('CURRENT_TIMESTAMP'),\n" .
                "        ]);";
            $down = "        \$this->dropTable('{$operation['table']}');";
        } elseif ($operation['type'] === 'drop') {
            $up = "        \$this->dropTable('{$operation['table']}');";
            $down =
                "        \$this->createTable('{$operation['table']}', [\n" .
                "            'id' => \$this->pk(),\n" .
                "        ]);";
        } elseif ($operation['type'] === 'add') {
            $up = "        \$this->addColumn('{$operation['table']}', '{$operation['column']}', \$this->string());";
            $down = "        \$this->dropColumn('{$operation['table']}', '{$operation['column']}');";
        } elseif ($operation['type'] === 'remove') {
            $up = "        \$this->dropColumn('{$operation['table']}', '{$operation['column']}');";
            $down = "        \$this->addColumn('{$operation['table']}', '{$operation['column']}', \$this->string());";
        } else {
            $table = $operation['table'];
            $up = "        // TODO: implement migration for {$name}.\n        // \$this->...('{$table}', ...);";
            $down = "        // TODO: implement rollback for {$name}.";
        }

        return "<?php\n" .
            "declare(strict_types=1);\n\n" .
            "use app\\Migration;\n\n" .
            "class {$class} extends Migration\n" .
            "{\n" .
            "    public function up(): void\n" .
            "    {\n" .
            $up . "\n" .
            "    }\n\n" .
            "    public function down(): void\n" .
            "    {\n" .
            $down . "\n" .
            "    }\n" .
            "}\n";
    }

    private function parseOperation(string $name): array
    {
        if (preg_match('/^create_(.+?)(?:_table)?$/', $name, $m)) {
            return ['type' => 'create', 'table' => $m[1]];
        }

        if (preg_match('/^drop_(.+?)(?:_table)?$/', $name, $m)) {
            return ['type' => 'drop', 'table' => $m[1]];
        }

        if (preg_match('/^add_(.+)_to_(.+)$/', $name, $m)) {
            return ['type' => 'add', 'column' => $m[1], 'table' => $m[2]];
        }

        if (preg_match('/^remove_(.+)_from_(.+)$/', $name, $m)) {
            return ['type' => 'remove', 'column' => $m[1], 'table' => $m[2]];
        }

        return ['type' => 'unknown', 'table' => $name];
    }

    private function resolveDirectory(string $root, string $dir): string
    {
        $dir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
        $dir = trim($dir, DIRECTORY_SEPARATOR);

        if ($dir === '' || $dir === '.') {
            return $root . DIRECTORY_SEPARATOR . 'migrations';
        }

        $parts = explode(DIRECTORY_SEPARATOR, $dir);
        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..' || !preg_match('/^[A-Za-z0-9_.-]+$/', $part)) {
                throw new RuntimeException("Bad migration directory: {$dir}");
            }
        }

        return $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function toSnake(string $s): string
    {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9_]+/', '_', $s);
        return trim($s, '_');
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) {
            throw new RuntimeException("File exists: {$path} (use --force)");
        }
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Cannot write migration: {$path}");
        }
    }
}
