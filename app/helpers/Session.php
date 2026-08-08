<?php

declare(strict_types=1);

namespace app\helpers;

final class Session
{
    public function __construct(private array $data = [])
    {
    }

    public static function fromGlobals(): self
    {
        return new self($_SESSION ?? []);
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $_SESSION[$key] = $value;
    }

    public function setArray(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value);
        }
    }

    public function remove(string $key): void
    {
        unset($this->data[$key], $_SESSION[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }
}
