<?php
declare(strict_types=1);

namespace app\console;

use app\Db;
use RuntimeException;

class MakeModelCommand implements CommandInterface
{
    public function name(): string { return 'make:model'; }
    public function description(): string { return 'Generate ActiveRecord model from DB table (PHP 7.4)'; }

    public function execute(Input $in, Output $out): int
    {
        $class = (string)$in->arg(0, '');
        $table = (string)$in->opt('table', '');

        if ($class === '' || $table === '') {
            $out->line("Usage: php bin/console.php make:model User --table=user [--force]");
            return 1;
        }

        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $class)) {
            $out->err("Bad class name: $class");
            return 1;
        }

        // защищаем DESCRIBE от мусора
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            $out->err("Bad table name: $table");
            return 1;
        }

        $pdo = Db::getInstance();

        $stmt = $pdo->query("DESCRIBE `$table`");
        $cols = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        if (!$cols) {
            $out->err("Table not found or no columns: $table");
            return 1;
        }

        $namespace = (string)$in->opt('namespace', 'app\\models');
        $dirOpt    = (string)$in->opt('dir', 'app/models');

        $root = dirname(__DIR__, 2); // .../app/console -> project root
        $targetDir = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirOpt);
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $class . '.php';

        $phpdoc = $this->buildPhpDoc($cols);

        $code =
            "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use app\\ActiveRecord;\n\n" .
            $phpdoc . "\n" .
            "class {$class} extends ActiveRecord\n" .
            "{\n" .
            "    public static function tableName(): string\n" .
            "    {\n" .
            "        return '{$table}';\n" .
            "    }\n" .
            "}\n";

        $this->writeFile($targetFile, $code, $in->has('force'));

        $out->line("OK: {$targetFile}");
        return 0;
    }

    private function buildPhpDoc(array $cols): string
    {
        $lines = ["/**"];
        foreach ($cols as $c) {
            $field = $c['Field'] ?? '';
            if ($field === '') continue;

            $sqlType = (string)($c['Type'] ?? '');
            $nullable = ((string)($c['Null'] ?? 'NO')) === 'YES';

            $type = $this->mapSqlTypeToPhpDoc($sqlType);
            if ($nullable) $type .= '|null';

            $lines[] = " * @property {$type} \${$field}";
        }
        $lines[] = " */";
        return implode("\n", $lines);
    }

    private function mapSqlTypeToPhpDoc(string $sqlType): string
    {
        $t = strtolower($sqlType);

        if (strpos($t, 'tinyint(1)') !== false || strpos($t, 'bool') !== false) return 'bool';
        if (strpos($t, 'int') !== false) return 'int';
        if (strpos($t, 'decimal') !== false || strpos($t, 'float') !== false || strpos($t, 'double') !== false) return 'float';

        // даты/время обычно строкой в AR, но можешь потом поменять на DateTime
        if (strpos($t, 'date') !== false || strpos($t, 'time') !== false || strpos($t, 'timestamp') !== false) return 'string';

        return 'string';
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) {
            throw new RuntimeException("File exists: {$path} (use --force)");
        }

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        file_put_contents($path, $content);
    }
}
