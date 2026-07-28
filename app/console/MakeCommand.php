<?php
declare(strict_types=1);

namespace app\console;

use RuntimeException;

class MakeCommand implements CommandInterface
{
    public function name(): string { return 'command'; }
    public function description(): string { return 'Create commands'; }

    public function execute(Input $in, Output $out): int
    {
        $name = (string)$in->arg(0, '');

        if ($name === '') {
            $out->line("Usage: php bin/console.php make:command {CommandName} [--force]");
            return 0;
        }
        $nameIsUpper = ucFirst($name);

        $code = "<?php
declare(strict_types=1);

namespace app\console;

class {$nameIsUpper}Command implements CommandInterface
{
    public function name(): string { return '$name'; }
    public function description(): string { return 'Create command to $name'; }

    public function execute(Input \$in, Output \$out): int
    {
        // code here
        // Add register command to ConsoleApplication.php
    }
}
";

        $root = dirname(__DIR__, 2); // .../app/console -> project root
        $targetDir = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, "app/console");
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $nameIsUpper."Command" . '.php';

        $this->writeFile($targetFile, $code, $in->has('force'));
        $out->line("OK: {$targetFile}");
        return 0;
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
