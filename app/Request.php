<?php

declare(strict_types=1);

namespace app;

use InvalidArgumentException;

final class Request
{
    private ?array $json = null;

    public function __construct(
        private array $server = [],
        private array $queryParams = [],
        private array $postParams = [],
        private ?string $rawBody = null,
    ) {
        if ($this->server === []) {
            $this->server = $_SERVER;
        }
        if ($this->queryParams === []) {
            $this->queryParams = $_GET;
        }
        if ($this->postParams === []) {
            $this->postParams = $_POST;
        }
    }

    public function getSegments(): array
    {
        $path = trim($this->path(), '/');
        if ($path === '') {
            return [];
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '') {
                continue;
            }
            $segments[] = rawurldecode($segment);
        }

        return $segments;
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->queryParams : ($this->queryParams[$key] ?? $default);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->postParams : ($this->postParams[$key] ?? $default);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }

    public function rawData(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $raw = $this->rawBody;
        if ($raw === null) {
            $raw = file_get_contents('php://input');
        }

        if ($raw === false || trim($raw) === '') {
            return $this->json = [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON request body.', 400, $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('JSON request body must contain an object or array.', 400);
        }

        return $this->json = $decoded;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $normalized = strtoupper(str_replace('-', '_', $name));
        $key = str_starts_with($normalized, 'HTTP_') ? $normalized : 'HTTP_' . $normalized;

        if (array_key_exists($key, $this->server)) {
            return $this->server[$key];
        }

        return $this->server[$normalized] ?? $default;
    }

    public function all(): array
    {
        return $this->queryParams + $this->postParams;
    }
}
