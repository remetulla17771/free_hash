<?php

declare(strict_types=1);

namespace app;

class Request
{
    private ?array $json = null;

    public function getSegments(): array
    {
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        return $path === '' ? [] : explode('/', $path);
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    public function rawData(): mixed
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $this->json = [];
        }

        return $this->json = json_decode($raw, true) ?? [];
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? $default;
    }
}
