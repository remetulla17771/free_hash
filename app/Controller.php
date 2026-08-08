<?php

declare(strict_types=1);

namespace app;

class Controller
{
    public string $layout = 'main';

    public Request $request;
    public Response $response;
    public Container $container;

    // Temporary compatibility for the existing application layer.
    public mixed $user = null;
    public mixed $urlManager = null;
    public mixed $language = null;
    public mixed $session = null;
    public mixed $arrayHelper = null;
    public mixed $stringer = null;

    private array $config;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->get(Request::class);
        $this->response = $container->get(Response::class);
        $this->config = $container->get(App::class)->config();

        $this->user = $this->getComponent('user');
        $this->urlManager = $this->getComponent('urlManager');
        $this->language = $this->getComponent('language');
        $this->session = $this->getComponent('session');
        $this->arrayHelper = $this->getComponent('arrayHelper');
        $this->stringer = $this->getComponent('stringer');
    }

    private function getComponent(string $id): mixed
    {
        return $this->container->has($id) ? $this->container->get($id) : null;
    }

    protected function getModuleId(): ?string
    {
        $namespace = (new \ReflectionClass($this))->getNamespaceName();
        if (!str_starts_with($namespace, 'modules\\')) {
            return null;
        }

        $parts = explode('\\', $namespace);
        return $parts[1] ?? null;
    }

    protected function getBaseViewPath(): string
    {
        $moduleId = $this->getModuleId();
        return $moduleId
            ? __DIR__ . "/../modules/{$moduleId}/views"
            : __DIR__ . '/../views';
    }

    public function createUrl(array $route): string
    {
        $path = trim((string) ($route[0] ?? ''), '/');
        unset($route[0]);
        return '/' . $path . (!empty($route) ? '?' . http_build_query($route) : '');
    }

    public function redirect(string|array $url, int $status = 302): Response
    {
        if (is_array($url)) {
            $path = trim((string) ($url[0] ?? ''), '/');
            unset($url[0]);
            $url = '/' . $path . (!empty($url) ? '?' . http_build_query($url) : '');
        }

        return $this->response->redirect($url, $status);
    }

    public function render(string $view, array $params = []): string
    {
        return $this->renderLayout($this->renderView($view, $params));
    }

    protected function renderView(string $view, array $params): string
    {
        $controller = strtolower(
            str_replace('Controller', '', (new \ReflectionClass($this))->getShortName())
        );

        $viewFile = $this->getBaseViewPath() . "/{$controller}/{$view}.php";
        if (!is_file($viewFile)) {
            throw new \RuntimeException('Не найден вид: ' . $viewFile, 500);
        }

        extract($params, EXTR_SKIP);
        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    public function renderPartial(string $view, array $params = []): string
    {
        return $this->renderView($view, $params);
    }

    protected function renderLayout(string $content): string
    {
        $base = $this->getBaseViewPath();
        $layoutFile = $base . "/layouts/{$this->layout}.php";

        if (!is_file($layoutFile)) {
            $layoutFile = __DIR__ . "/../views/layouts/{$this->layout}.php";
        }

        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layoutFile}", 500);
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    public function config(?string $key = null): mixed
    {
        return $key === null ? $this->config : ($this->config[$key] ?? null);
    }

    public function t(string $category, string $message, array $params = []): string
    {
        return \app\helpers\I18n::t($category, $message, $params);
    }

    public function dd(mixed $value, bool $die = true): void
    {
        echo '<pre>';
        print_r($value);
        echo '</pre>';
        if ($die) {
            die;
        }
    }

    public function actions(): array
    {
        return [];
    }
}
