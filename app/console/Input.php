<?php
declare(strict_types=1);

namespace app\console;

class Input
{
    private array $options = [];
    private array $args = [];
    private ?string $command = null;

    public function __construct(array $argv)
    {
        $parts = $argv;
        array_shift($parts); // script
        $this->command = $parts[0] ?? null;
        if ($this->command !== null) array_shift($parts);

        foreach ($parts as $p) {
            if (strncmp($p, '--', 2) === 0) {
                $eq = strpos($p, '=');
                if ($eq !== false) {
                    $k = substr($p, 2, $eq - 2);
                    $v = substr($p, $eq + 1);
                    $this->options[$k] = $v;
                } else {
                    $this->options[substr($p, 2)] = true;
                }
            } else {
                $this->args[] = $p;
            }
        }

    }

    public function command(): ?string { return $this->command; }
    public function arg(int $i, $default = null)
    {
        return array_key_exists($i, $this->args) ? $this->args[$i] : $default;
    }

    public function opt(string $k, $default = null)
    {
        return array_key_exists($k, $this->options) ? $this->options[$k] : $default;
    }

    public function has(string $k): bool { return array_key_exists($k, $this->options); }
}
