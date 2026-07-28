<?php

namespace app;

use ReflectionClass;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

    /* =====================
       FACTORIES
    ===================== */

    public static function html(string $content, int $status = 200): self
    {
        $res = new self();
        $res->statusCode = $status;
        $res->content = $content;
        $res->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $res;
    }

    public static function json($data, int $status = 200): self
    {
        $res = new self();
        $res->statusCode = $status;

        if (is_array($data)) {
            $data = array_map(function ($item) {
                return $item instanceof \app\ActiveRecord
                    ? $item->toArray()
                    : $item;
            }, $data);
        } elseif ($data instanceof \app\ActiveRecord) {
            $data = $data->toArray();
        }

        $res->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res->setHeader('Content-Type', 'application/json');

        return $res;
    }

    protected static function createUrl($route): string
    {


        $path = '?' . http_build_query($route);


        return '/' . $path;
    }

    public static function redirect($url, int $status = 302, $controller = null): self
    {
        $res = new self();
        $res->statusCode = $status;

        $controllerName = null;
        if ($controller !== null) {
            // Если передан ReflectionClass, берем короткое имя класса (например, SiteController)
            if ($controller instanceof \ReflectionClass) {
                $shortName = $controller->getShortName();
            } else {
                // Фолбек на случай, если передали обычную строку или объект
                $className = is_object($controller) ? get_class($controller) : $controller;
                $parts = explode('\\', $className);
                $shortName = end($parts);
            }

            // Отрезаем суффикс "Controller" и приводим первую букву к нижнему регистру (SiteController -> site)
            $controllerName = lcfirst(str_replace('Controller', '', $shortName));
        }

        if (is_array($url)) {
            $url = self::buildUrl($url, $controllerName);
        }
        else $url = $url;

        $res->setHeader('Location', $url);
        return $res;
    }

    public static function error(int $code, string $message = ''): self
    {
        return self::html($message, $code);
    }

    /* =====================
       CORE
    ===================== */

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
        exit;
    }

    /* =====================
       HELPERS
    ===================== */

    protected static function buildUrl(array $route, ?string $controller = null): string
    {
        $path = $route[0];
        unset($route[0]);

        // 1. Проверяем, является ли путь абсолютным (начинается с '/')
        $isAbsolute = (strpos($path, '/') === 0);
        $path = trim($path, '/');

        // 2. Проверяем, указан ли уже какой-то контроллер в пути (есть ли слэш внутри, например 'site/index')
        $hasControllerInPath = (strpos($path, '/') !== false);

        // Если путь относительный, в нем нет своего контроллера, и нам передан текущий контроллер
        if (!$isAbsolute && !$hasControllerInPath && $controller !== null) {
            $path = $controller . '/' . $path;
        }

        // 3. Добавляем GET-параметры (например, 'page' => 1 превратится в ?page=1)
        if (!empty($route)) {
            $path .= '?' . http_build_query($route);
        }

        return '/' . $path;
    }
}
