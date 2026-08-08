<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

final class ViewRenderer
{
    public function __construct(private string $basePath)
    {
    }

    public function render(string $view, array $params = [], ?string $layout = 'main'): string
    {
        $content = $this->renderFile($this->resolveView($view), $params);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($this->resolveLayout($layout), [
            'content' => $content,
        ] + $params);
    }

    public function partial(string $view, array $params = []): string
    {
        return $this->renderFile($this->resolveView($view), $params);
    }

    private function renderFile(string $file, array $params): string
    {
        extract($params, EXTR_SKIP);

        ob_start();
        try {
            require $file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private function resolveView(string $view): string
    {
        $file = $this->basePath . '/' . trim($view, '/') . '.php';
        $this->assertInsideBasePath($file);

        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $view, 500);
        }

        return $file;
    }

    private function resolveLayout(string $layout): string
    {
        $file = $this->basePath . '/layouts/' . trim($layout, '/') . '.php';
        $this->assertInsideBasePath($file);

        if (!is_file($file)) {
            throw new RuntimeException('Layout not found: ' . $layout, 500);
        }

        return $file;
    }

    private function assertInsideBasePath(string $file): void
    {
        $base = realpath($this->basePath);
        $directory = realpath(dirname($file));

        if ($base === false || $directory === false || ($directory !== $base && !str_starts_with($directory, $base . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Invalid view path.', 500);
        }
    }
}
