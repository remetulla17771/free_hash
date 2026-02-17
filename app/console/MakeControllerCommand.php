<?php
declare(strict_types=1);

namespace app\console;

class MakeControllerCommand implements CommandInterface
{
    public function name(): string { return 'make:controller'; }
    public function description(): string { return 'Generate controller + default view folder'; }

    public function execute(Input $in, Output $out): int
    {
        $name = (string)$in->arg(0, '');
        if ($name === '') {
            $out->line("Usage: php bin/console make:controller Site [--force]");
            return 1;
        }

        $class = preg_replace('/Controller$/', '', $name) . 'Controller';
        $controllerId = strtolower(preg_replace('/Controller$/', '', $class));

        $root = dirname(__DIR__, 2); // .../app/console -> root
        $controllerFile = $root . "/app/controllers/{$class}.php";
        $viewsDir = $root . "/views/{$controllerId}";
        $viewIndex = $viewsDir . "/index.php";

        $code = <<<PHP
<?php
namespace app\\controllers;

use app\\Controller;

class {$class} extends Controller
{
    public function actionIndex()
    {
        return \$this->render('index');
    }
}

PHP;

        $this->write($controllerFile, $code, $in->has('force'));

        if (!is_dir($viewsDir)) mkdir($viewsDir, 0777, true);
        $this->write($viewIndex, "<h1>{$controllerId}/index</h1>\n", $in->has('force'));

        $out->line("OK: {$controllerFile}");
        $out->line("OK: {$viewIndex}");
        return 0;
    }

    private function write(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) {
            throw new \RuntimeException("File exists: {$path} (use --force)");
        }
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($path, $content);
    }
}
