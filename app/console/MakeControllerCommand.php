<?php

declare(strict_types=1);

namespace app\console;

use RuntimeException;

final class MakeControllerCommand implements CommandInterface
{
    public function name(): string { return 'make:controller'; }
    public function description(): string { return 'Generate controller and default view'; }

    public function execute(Input $in, Output $out): int
    {
        $name = trim((string) $in->arg(0, ''));
        if ($name === '') {
            $out->line('Usage: php console make:controller Site [--force]');
            return 1;
        }

        $base = preg_replace('/Controller$/', '', $name) ?? $name;
        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $base)) {
            $out->err("Bad controller name: {$name}");
            return 1;
        }

        $class = $base . 'Controller';
        $controllerId = strtolower($base);
        $root = dirname(__DIR__, 2);
        $controllerFile = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $class . '.php';
        $viewsDir = $root . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $controllerId;
        $viewIndex = $viewsDir . DIRECTORY_SEPARATOR . 'index.php';

        $code = "<?php\n\ndeclare(strict_types=1);\n\nnamespace app\\controllers;\n\nuse app\\Controller;\n\nfinal class {$class} extends Controller\n{\n    public function actionIndex()\n    {\n        return \$this->render('index');\n    }\n}\n";

        $this->write($controllerFile, $code, $in->has('force'));
        if (!is_dir($viewsDir) && !mkdir($viewsDir, 0777, true) && !is_dir($viewsDir)) throw new RuntimeException("Unable to create directory: {$viewsDir}");
        $this->write($viewIndex, "<h1>{$controllerId}/index</h1>\n", $in->has('force'));

        $out->line("OK: {$controllerFile}");
        $out->line("OK: {$viewIndex}");
        return 0;
    }

    private function write(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) throw new RuntimeException("File exists: {$path} (use --force)");
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) throw new RuntimeException("Unable to create directory: {$dir}");
        if (file_put_contents($path, $content) === false) throw new RuntimeException("Unable to write file: {$path}");
    }
}
