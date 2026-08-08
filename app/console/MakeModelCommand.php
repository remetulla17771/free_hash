<?php

declare(strict_types=1);

namespace app\console;

use app\Db;
use RuntimeException;

final class MakeModelCommand implements CommandInterface
{
    public function __construct(private Db $db)
    {
    }

    public function name(): string { return 'make:model'; }
    public function description(): string { return 'Generate ActiveRecord model from DB table'; }

    public function execute(Input $in, Output $out): int
    {
        $class = (string) $in->arg(0, '');
        $table = (string) $in->opt('table', '');

        if ($class === '' || $table === '') {
            $out->line('Usage: php console make:model User --table=user [--force]');
            return 1;
        }
        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $class)) {
            $out->err("Bad class name: {$class}");
            return 1;
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            $out->err("Bad table name: {$table}");
            return 1;
        }

        $stmt = $this->db->pdo()->query("DESCRIBE `{$table}`");
        $cols = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!$cols) {
            $out->err("Table not found or no columns: {$table}");
            return 1;
        }

        $namespace = (string) $in->opt('namespace', 'app\\models');
        $dirOpt = (string) $in->opt('dir', 'app/models');
        $root = dirname(__DIR__, 2);
        $targetDir = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirOpt);
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $class . '.php';

        $labels = '';
        $phpdoc = [ '/**' ];
        foreach ($cols as $column) {
            $field = (string) ($column['Field'] ?? '');
            if ($field === '') continue;
            $type = $this->mapSqlTypeToPhpDoc((string) ($column['Type'] ?? ''));
            if ((string) ($column['Null'] ?? 'NO') === 'YES') $type .= '|null';
            $phpdoc[] = " * @property {$type} \${$field}";
            $labels .= "            '{$field}' => '" . ucwords(str_replace('_', ' ', $field)) . "',\n";
        }
        $phpdoc[] = ' */';

        $code = "<?php\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse app\\ActiveRecord;\n\n"
            . implode("\n", $phpdoc) . "\n"
            . "class {$class} extends ActiveRecord\n{\n"
            . "    public static function tableName(): string\n    {\n        return '{$table}';\n    }\n\n"
            . "    public function attributeLabels(): array\n    {\n        return [\n{$labels}        ];\n    }\n}\n";

        $this->writeFile($targetFile, $code, $in->has('force'));
        $out->line("OK: {$targetFile}");
        return 0;
    }

    private function mapSqlTypeToPhpDoc(string $sqlType): string
    {
        $type = strtolower($sqlType);
        if (str_contains($type, 'tinyint(1)') || str_contains($type, 'bool')) return 'bool';
        if (str_contains($type, 'int')) return 'int';
        if (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) return 'float';
        return 'string';
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) {
            throw new RuntimeException("File exists: {$path} (use --force)");
        }
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Unable to write file: {$path}");
        }
    }
}
