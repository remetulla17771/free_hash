<?php

declare(strict_types=1);

namespace app;

interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}
