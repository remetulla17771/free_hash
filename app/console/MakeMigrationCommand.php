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
        $rawName = (string)$in->arg(0, '');
        if ($rawName === '') {
            $out->line("Usage: php bin/console.php make:migration create_users_table [--dir=migrations] [--force]");
            return 1;
        }

        $dir = (string)$in->opt('dir', 'migrations');
        $force = $in->has('force');

        $name = $this->toSnake($rawName);
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            $out->err("Bad migration name: $rawName");
            return 1;
        }

        $ts = date('ymd_His'); // как yii: yymmdd_hhmmss
        $class = 'm' . $ts . '_' . $name;

        $root = dirname(__DIR__, 2);
        $dirPath = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
        if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);

        $file = $dirPath . DIRECTORY_SEPARATOR . $class . '.php';

        $name = $this->toSnake($rawName);
        $tableName = $this->guessTableName($name);


        $code =
            "<?php\n" .
            "declare(strict_types=1);\n\n" .
            "use app\\Migration;\n\n" .
            "class {$class} extends Migration\n" .
            "{\n" .
            "    public function up(): void\n" .
            "    {\n" .
            "        \$this->createTable('{$tableName}', [\n" .
            "            'id' => \$this->pk(),\n" .
            "            'created_at' => \$this->timestamp() . ' ' . \$this->notNull() . ' ' . \$this->defaultExpr('CURRENT_TIMESTAMP'),\n" .
            "        ]);\n" .
            "    }\n\n" .
            "    public function down(): void\n" .
            "    {\n" .
            "        \$this->dropTable('{$tableName}');\n" .
            "    }\n" .
            "}\n";


        $this->writeFile($file, $code, $force);

        $out->line("OK: $file");
        return 0;
    }

    private function guessTableName(string $snakeMigrationName): string
    {
        // ожидаем форматы:
        // create_user_table -> user
        // create_users_table -> users
        // create_user -> user
        // add_token_to_user -> user (тут таблица после _to_)
        // drop_user_table -> user

        if (preg_match('/^(create|drop)_(.+?)_table$/', $snakeMigrationName, $m)) {
            return $m[2];
        }
        if (preg_match('/^(create|drop)_(.+)$/', $snakeMigrationName, $m)) {
            return $m[2];
        }
        if (preg_match('/^add_.+_to_(.+)$/', $snakeMigrationName, $m)) {
            return $m[1];
        }
        if (preg_match('/^remove_.+_from_(.+)$/', $snakeMigrationName, $m)) {
            return $m[1];
        }

        // fallback: если не угадали — пусть будет имя миграции
        return $snakeMigrationName;
    }


    private function toSnake(string $s): string
    {
        $s = preg_replace('/([a-z])([A-Z])/', '$1_$2', $s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9_]+/', '_', $s);
        $s = trim($s, '_');
        return $s;
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) {
            throw new RuntimeException("File exists: {$path} (use --force)");
        }
        file_put_contents($path, $content);
    }
}
