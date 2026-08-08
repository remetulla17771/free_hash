<?php

declare(strict_types=1);

namespace app\console;

use RuntimeException;

final class MakeCommand implements CommandInterface
{
    public function name(): string { return 'make:command'; }
    public function description(): string { return 'Create a custom console command'; }

    public function execute(Input $in, Output $out): int
    {
        $name = trim((string) $in->arg(0, ''));
        if ($name === '') {
            $out->line('Usage: php console make:command ExampleCommand [--force]');
            return 1;
        }

        $name = preg_replace('/Command$/', '', $name) ?? $name;
        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $name)) {
            $out->err("Bad command class name: {$name}");
            return 1;
        }

        $commandName = strtolower(preg_replace('/(?<!^)[A-Z]/', ':$0', $name) ?? $name);
        $class = $name . 'Command';
        $code = "<?php\n\ndeclare(strict_types=1);\n\nnamespace app\\console;\n\nfinal class {$class} implements CommandInterface\n{\n    public function name(): string\n    {\n        return '{$commandName}';\n    }\n\n    public function description(): string\n    {\n        return 'Custom {$name} command';\n    }\n\n    public function execute(Input \$in, Output \$out): int\n    {\n        \$out->line('{$commandName} executed.');\n        return 0;\n    }\n}\n";

        $root = dirname(__DIR__, 2);
        $targetDir = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'console';
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $class . '.php';

        $this->writeFile($targetFile, $code, $in->has('force'));
        $out->line("OK: {$targetFile}");
        $out->line("Run: php console {$commandName}");
        return 0;
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) throw new RuntimeException("File exists: {$path} (use --force)");
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) throw new RuntimeException("Unable to create directory: {$dir}");
        if (file_put_contents($path, $content) === false) throw new RuntimeException("Unable to write file: {$path}");
    }
}
