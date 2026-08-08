<?php

declare(strict_types=1);

namespace app;

final class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public static function html(string $content, int $status = 200): self
    {
        return (new self())
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setContent($content);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        if ($data instanceof ActiveRecord) {
            $data = $data->toArray();
        } elseif (is_array($data)) {
            $data = array_map(static fn ($item) =>
                $item instanceof ActiveRecord ? $item->toArray() : $item,
                $data
            );
        }

        return (new self())
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function redirect(string $url, int $status = 302): self
    {
        if ($status < 300 || $status > 399) {
            throw new \InvalidArgumentException('Redirect status must be between 300 and 399.');
        }

        return (new self())
            ->setStatusCode($status)
            ->setHeader('Location', $url);
    }

    public static function error(int $code, string $message = ''): self
    {
        return self::html($message, $code);
    }

    public function setStatusCode(int $statusCode): self
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new \InvalidArgumentException('Invalid HTTP status code.');
        }

        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        $name = trim($name);
        if ($name === '' || preg_match('/[\r\n]/', $name . $value)) {
            throw new \InvalidArgumentException('Invalid HTTP header.');
        }

        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
