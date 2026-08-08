<?php

declare(strict_types=1);

namespace app;

final class Route
{
    public function __construct(
        public readonly string $controller,
        public readonly string $action,
        public readonly array $parameters = [],
    ) {
    }
}
