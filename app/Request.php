<?php

declare(strict_types=1);

namespace app;

final class Request
{
    private ?array $json = null;
    private array $server;
    private array $queryParams;
    private array $postParams;

    public function __construct(
        ?array $server = null,
        ?array $query = null,
        ?array $post = null,
        private ?string $rawBody = null,
    ) {
        $this->server = $server ?? $_SERVER;
        $this->queryParams = $query ?? $_GET;
        $this->postParams = $post ?? $_POST;
    }

    public function getSegments(): array
    {
        $path = trim($this->path(), '/');
        return $path === '' ? [] : array_values(array_filter(explode('/', $path), static fn ($part) => $part !== ''));
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
        return (string) (parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    }

    public function rawData(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $raw = $this->rawBody ?? file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $this->json = [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid JSON request body.', 400);
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

        $contentHeaders = [
            'CONTENT_TYPE' => 'CONTENT_TYPE',
            'CONTENT_LENGTH' => 'CONTENT_LENGTH',
            'CONTENT_MD5' => 'CONTENT_MD5',
        ];

        $key = $contentHeaders[$normalized] ?? $key;
        return $this->server[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->queryParams + $this->postParams;
    }
}
