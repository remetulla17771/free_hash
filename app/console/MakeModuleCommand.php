<?php

declare(strict_types=1);

namespace app\console;

use RuntimeException;

final class MakeModuleCommand implements CommandInterface
{
    public function name(): string { return 'make:module'; }
    public function description(): string { return 'Generate a module skeleton'; }

    public function execute(Input $in, Output $out): int
    {
        $id = trim((string) $in->arg(0, ''));
        if ($id === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $id)) {
            $out->line('Usage: php console make:module admin [--force]');
            return 1;
        }

        $force = $in->has('force');
        $root = dirname(__DIR__, 2);
        $moduleDir = $root . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $id;

        foreach (['controllers', 'views/layouts', 'views/default'] as $dir) {
            $path = $moduleDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) throw new RuntimeException("Unable to create directory: {$path}");
        }

        $moduleCode = "<?php\n\ndeclare(strict_types=1);\n\nnamespace modules\\{$id};\n\nuse app\\Module as BaseModule;\n\nfinal class Module extends BaseModule\n{\n}\n";
        $controllerCode = "<?php\n\ndeclare(strict_types=1);\n\nnamespace modules\\{$id}\\controllers;\n\nuse app\\Controller;\n\nfinal class DefaultController extends Controller\n{\n    public function actionIndex()\n    {\n        return \$this->render('index');\n    }\n}\n";
        $viewCode = "<h1>Module '{$id}' works</h1>\n<p>Open: <code>/{$id}/default/index</code></p>\n";
        $layoutCode = "<?php\n\ndeclare(strict_types=1);\n\nuse app\\App;\nuse app\\assets\\AppAsset;\nuse app\\helpers\\Alert;\nuse app\\helpers\\MetaTagManager;\nuse app\\helpers\\NavBar;\n?>\n<!DOCTYPE html>\n<html lang=\"<?= \$this->language->get() ?>\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <?= MetaTagManager::render() ?>\n    <title><?= \$this->title ?></title>\n    <?php (new AppAsset)->registerCss(); ?>\n</head>\n<body class=\"d-flex flex-column h-80\">\n<header>\n<?php new NavBar([\n    'brandLabel' => \$this->config('appName'),\n    'brandUrl' => '/site/index',\n    'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top'],\n    'ulClass' => 'navbar-nav navbar-collapse justify-content-end nav',\n    'items' => [\n        ['label' => 'Home', 'url' => '/{$id}/default/index'],\n        \$this->user->isGuest() ? ['label' => 'Login', 'url' => '/site/login'] : ['label' => \$this->user->identity('login') . ' (Logout)', 'url' => '/site/logout']\n    ],\n]); ?>\n</header>\n<main class=\"container\" style=\"height: 100vh; margin-top: 80px;\">\n<?= Alert::getAll() ?>\n<?= \$content ?>\n</main>\n<footer id=\"footer\" class=\"mt-auto py-2 bg-light\"><div class=\"container\"><div class=\"row text-muted\"><div class=\"col-md-6 text-center text-md-start\">&copy; <?= date('Y') ?> My MVC App</div><div class=\"col-md-6 text-center text-md-end\"><?= App::powered() ?></div></div></div></footer>\n<?php (new AppAsset)->registerJs(); ?>\n</body>\n</html>\n";

        $files = [
            $moduleDir . DIRECTORY_SEPARATOR . 'Module.php' => $moduleCode,
            $moduleDir . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'DefaultController.php' => $controllerCode,
            $moduleDir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php' => $layoutCode,
            $moduleDir . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'index.php' => $viewCode,
        ];

        foreach ($files as $file => $content) $this->writeFile($file, $content, $force);

        $out->line("OK: modules/{$id} created");
        $out->line("Register it in config as modules.{$id}.class = modules\\{$id}\\Module");
        return 0;
    }

    private function writeFile(string $path, string $content, bool $force): void
    {
        if (file_exists($path) && !$force) throw new RuntimeException("File exists: {$path} (use --force)");
        if (file_put_contents($path, $content) === false) throw new RuntimeException("Unable to write file: {$path}");
    }
}
