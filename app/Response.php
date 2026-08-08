<?php

declare(strict_types=1);

namespace app;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

    public static function html(string $content, int $status = 200): self
    {
        $res = new self();
        $res->statusCode = $status;
        $res->content = $content;
        $res->setHeader('Content-Type', 'text/html; charset=utf-8');
        return $res;
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $res = new self();
        $res->statusCode = $status;

        if (is_array($data)) {
            $data = array_map(static fn ($item) =>
                $item instanceof ActiveRecord ? $item->toArray() : $item,
                $data
            );
        } elseif ($data instanceof ActiveRecord) {
            $data = $data->toArray();
        }

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $res->content = $encoded;
        $res->setHeader('Content-Type', 'application/json; charset=utf-8');

        return $res;
    }

    public static function redirect(string $url, int $status = 302): self
    {
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
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
