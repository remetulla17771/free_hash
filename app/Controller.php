<?php

declare(strict_types=1);

namespace app;

class Controller
{
    public string $layout = 'main';

    public Request $request;
    public Response $response;
    public Container $container;
    public ViewRenderer $view;

    // Temporary compatibility for the existing application layer.
    public mixed $user = null;
    public mixed $urlManager = null;
    public mixed $language = null;
    public mixed $session = null;
    public mixed $arrayHelper = null;
    public mixed $stringer = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->get(Request::class);
        $this->response = $container->get(Response::class);
        $this->view = $container->get(ViewRenderer::class);

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
        $controller = strtolower(
            str_replace('Controller', '', (new \ReflectionClass($this))->getShortName())
        );

        return $this->view->render($controller . '/' . $view, $params, $this->layout);
    }

    public function renderPartial(string $view, array $params = []): string
    {
        $controller = strtolower(
            str_replace('Controller', '', (new \ReflectionClass($this))->getShortName())
        );

        return $this->view->partial($controller . '/' . $view, $params);
    }

    public function config(?string $key = null): mixed
    {
        $config = $this->container->get(App::class)->config();
        return $key === null ? $config : ($config[$key] ?? null);
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
