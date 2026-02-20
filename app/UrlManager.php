<?php
namespace app;

use app\helpers\I18n;

class UrlManager
{
    public ?string $module = null;

    public string $controller = 'site';
    public string $action = 'index';
    protected array $params = [];

    public function __construct()
    {
        $this->parse();
    }

    protected function parse(): void
    {
        // сначала режем язык (если есть), получаем сегменты без /ru
        $segments = self::parseRequest($_SERVER['REQUEST_URI']);

        if (empty($segments)) {
            return;
        }

        // module = первый сегмент, если есть папка modules/<module>
        $candidate = $segments[0] ?? null;
        if ($candidate && $this->isModuleId($candidate) && $this->moduleExists($candidate)) {
            $this->module = array_shift($segments);
        } else {
            $this->module = null;
        }

        $this->controller = $segments ? array_shift($segments) : 'site';
        $this->action     = $segments ? array_shift($segments) : 'index';
        $this->params     = $segments;
    }

    private function isModuleId(string $id): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_]+$/', $id);
    }

    private function moduleExists(string $id): bool
    {
        // UrlManager лежит в /app, модули в /modules
        $modulesDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'modules';
        return is_dir($modulesDir . DIRECTORY_SEPARATOR . $id);
    }

    // язык
    public static array $languages = ['ru', 'en', 'kz'];
    public static string $defaultLanguage = 'ru';

    public static function parseRequest(string $uri): array
    {
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        $lang = self::$defaultLanguage;

        if (!empty($segments) && in_array($segments[0], self::$languages, true)) {
            $lang = array_shift($segments);
        }

        I18n::$language = $lang;

        return $segments; // ВАЖНО: модуль НЕ вырезаем, он нужен Router-у
    }

    public function pasteUrlLanguage($lang)
    {
        $urlString = $_SERVER['REQUEST_URI'];

        $parts = parse_url($urlString);
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if ($lang) {
            $query['lang'] = $lang;
        }

        $newQuery = http_build_query($query);
        $urlString = $parts['path'] . ($newQuery ? '?' . $newQuery : '');

        return $urlString;
    }
}