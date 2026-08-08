<?php

declare(strict_types=1);

namespace app;

use app\helpers\I18n;

final class UrlManager
{
    public static array $languages = ['ru', 'en', 'kz'];
    public static string $defaultLanguage = 'ru';

    public ?string $module = null;
    public string $controller = 'site';
    public string $action = 'index';
    protected array $params = [];

    public function __construct(private Request $request, private ModuleManager $modules)
    {
        $this->parse();
    }

    protected function parse(): void
    {
        $segments = self::parseRequest($this->request->path());

        $candidate = $segments[0] ?? null;
        if ($candidate !== null && $this->modules->has($candidate)) {
            $this->module = array_shift($segments);
        }

        $this->controller = $segments ? array_shift($segments) : 'site';
        $this->action = $segments ? array_shift($segments) : 'index';
        $this->params = $segments;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public static function parseRequest(string $uri): array
    {
        $path = trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        $segments = $path === '' ? [] : explode('/', $path);

        $language = self::$defaultLanguage;
        if ($segments !== [] && in_array($segments[0], self::$languages, true)) {
            $language = array_shift($segments);
        }

        I18n::$language = $language;
        return $segments;
    }

    public function create(array|string $route, array $params = []): string
    {
        if (is_string($route)) {
            $path = trim($route, '/');
        } else {
            $path = trim((string) ($route[0] ?? ''), '/');
            unset($route[0]);
            $params = $route + $params;
        }

        return '/' . $path . ($params !== [] ? '?' . http_build_query($params) : '');
    }

    public function pasteUrlLanguage(?string $lang): string
    {
        $parts = parse_url($this->request->path());
        $path = $parts['path'] ?? '/';
        $query = [];

        if ($lang !== null && $lang !== '') {
            $query['lang'] = $lang;
        }

        return $path . ($query !== [] ? '?' . http_build_query($query) : '');
    }
}
