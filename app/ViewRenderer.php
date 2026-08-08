<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

final class ViewRenderer
{
    public function __construct(private string $basePath, private ?string $fallbackPath = null)
    {
    }

    public function render(string $view, array $params = [], ?string $layout = 'main'): string
    {
        $content = $this->partial($view, $params);

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
        return $this->resolveFile($this->basePath . '/' . trim($view, '/') . '.php', $view, null);
    }

    private function resolveLayout(string $layout): string
    {
        $file = $this->basePath . '/layouts/' . trim($layout, '/') . '.php';
        if (is_file($file)) {
            return $file;
        }

        if ($this->fallbackPath !== null) {
            $fallback = $this->fallbackPath . '/layouts/' . trim($layout, '/') . '.php';
            if (is_file($fallback)) {
                return $fallback;
            }
        }

        throw new RuntimeException('Layout not found: ' . $layout, 500);
    }

    private function resolveFile(string $file, string $name, ?string $fallback): string
    {
        if (is_file($file)) {
            return $file;
        }

        if ($fallback !== null && is_file($fallback)) {
            return $fallback;
        }

        throw new RuntimeException('View not found: ' . $name, 500);
    }
}
