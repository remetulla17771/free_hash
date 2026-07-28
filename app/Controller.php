<?php

namespace app;

class Controller extends App
{
    public string $layout = 'main';

    protected function getModuleId(): ?string
    {
        $ref = new \ReflectionClass($this);
        $ns = $ref->getNamespaceName(); // modules\admin\controllers или app\controllers

        if (strpos($ns, 'modules\\') !== 0) {
            return null;
        }

        $parts = explode('\\', $ns); // ['modules','admin','controllers']
        return $parts[1] ?? null;
    }

    protected function getBaseViewPath(): string
    {
        $moduleId = $this->getModuleId();
        if ($moduleId) {
            return __DIR__ . "/../modules/{$moduleId}/views";
        }
        return __DIR__ . "/../views";
    }

    protected function createUrl(array $route): string
    {
        $path = trim($route[0], '/');
        unset($route[0]);

        if (!empty($route)) {
            $path .= '?' . http_build_query($route);
        }

        return '/' . $path;
    }

    public function redirect($url, int $status = 302)
    {

        return $this->response->redirect($url, $status, $this->getControllerName());
    }

    public function render(string $view, array $params = []): string
    {
        $content = $this->renderView($view, $params);
        return $this->renderLayout($content);
    }

    protected function renderView(string $view, array $params): string
    {
        $controller = strtolower(
            str_replace('Controller', '', (new \ReflectionClass($this))->getShortName())
        );

        $base = $this->getBaseViewPath();
        $viewFile = $base . "/{$controller}/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new \Exception('Не найден вид: ' . $viewFile, 500);
        }

        extract($params, EXTR_SKIP);

        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    public function renderPartial(string $view, array $params = [])
    {
        return $this->renderView($view, $params);
    }

    protected function renderLayout(string $content): string
    {
        // сначала layout модуля, если контроллер из модуля
        $base = $this->getBaseViewPath();
        $layoutFile = $base . "/layouts/{$this->layout}.php";

        // если нет — глобальный layout
        if (!file_exists($layoutFile)) {
            $layoutFile = __DIR__ . "/../views/layouts/{$this->layout}.php";
        }

        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout not found: {$layoutFile}");
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    private function getControllerName()
    {
        return new \ReflectionClass($this);
    }

    public function actions()
    {
        return [];
    }

}